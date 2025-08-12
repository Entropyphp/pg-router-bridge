<?php

declare(strict_types=1);

namespace Entropy\Router\Command;

use Pg\Router\Route;
use Pg\Router\RouteCollectionInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function implode;
use function strtolower;

class RouteListCommand extends Command
{
    protected const NAME = 'route:list';
    private RouteCollectionInterface $collector;

    public function __construct(RouteCollectionInterface $collector)
    {
        parent::__construct(RouteListCommand::NAME);
        $this->collector = $collector;
    }

    protected function configure(): void
    {
        $this->setDescription('List all Routes from RouteCollection')
            ->setHelp('Get Application Routes definition (route:list)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $table = new Table($output);
        $table
            ->setHeaders(['Name:', 'Path:', 'Callback:', 'Methods:']);

        foreach ($this->collector->getRoutes() as $name => $route) {
            if ($route instanceof Route) {
                $table->addRow(
                    [
                        $name,
                        $route->getPath(),
                        $route->getCallback(),
                        $this->getMethods($route),
                    ]
                );
            }
        }

        $table->setColumnMaxWidth(0, 20);
        $table->setColumnMaxWidth(1, 20);
        $table->setColumnMaxWidth(2, 20);
        $table->setColumnMaxWidth(3, 8);

        $table->render();

        return self::SUCCESS;
    }

    private function getMethods(Route $route): string
    {
        if ($route->getAllowedMethods() === Route::HTTP_METHOD_ANY) {
            return '*';
        }

        $result = [];
        foreach ($route->getAllowedMethods() as $method) {
            $result[] = match (strtolower($method)) {
                'get' => '<fg=green>GET</>',
                'post' => '<fg=blue>POST</>',
                'patch' => '<fg=cyan>PATCH</>',
                'put' => '<fg=yellow>PUT</>',
                'delete' => '<fg=red>DELETE</>'
            };
        }

        return implode(', ', $result);
    }
}
