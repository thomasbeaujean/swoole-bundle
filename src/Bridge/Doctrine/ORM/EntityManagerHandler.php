<?php

declare(strict_types=1);

namespace K911\Swoole\Bridge\Doctrine\ORM;

use Doctrine\ORM\EntityManagerInterface;
use K911\Swoole\Server\RequestHandler\RequestHandlerInterface;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;

final class EntityManagerHandler implements RequestHandlerInterface
{
    public function __construct(
        private RequestHandlerInterface $decorated,
        private EntityManagerInterface $entityManager,
        private ManagerRegistry $managerRegistry,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Request $request, Response $response): void
    {
        $this->pingConnection($this->entityManager);
        $this->decorated->handle($request, $response);
        $this->entityManager->clear();
    }

    private function pingConnection(EntityManagerInterface $entityManager): void
    {
        $connection = $entityManager->getConnection();

        try {
            $this->executeDummySql($connection);
        } catch (DBALException) {
            $connection->close();
            // Attempt to reestablish the lazy connection by sending another query.
            $this->executeDummySql($connection);
        }

        if (!$entityManager->isOpen()) {
            $this->managerRegistry->resetManager();
        }
    }

    /**
     * @throws DBALException
     */
    private function executeDummySql(Connection $connection): void
    {
        $connection->executeQuery($connection->getDatabasePlatform()->getDummySelectSQL());
    }
}
