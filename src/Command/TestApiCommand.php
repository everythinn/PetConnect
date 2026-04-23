<?php
namespace App\Command;

use App\Controller\PetController;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

#[AsCommand(name: 'app:test-api')]
class TestApiCommand extends Command
{
    private $controller;
    private $em;

    public function __construct(PetController $controller, EntityManagerInterface $em)
    {
        $this->controller = $controller;
        $this->em = $em;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $user = $this->em->getRepository(User::class)->find(1);
        $response = $this->controller->getPets($user);
        $output->writeln($response->getContent());
        return Command::SUCCESS;
    }
}
