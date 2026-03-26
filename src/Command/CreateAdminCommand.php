<?php
declare(strict_types=1);

namespace App\Command;

use App\Entity\AdminUser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name       : 'app:create-admin',
    description: 'Create admin user from console',
)]
class CreateAdminCommand extends Command
{
    public function __construct(
        private EntityManagerInterface      $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function configure()
    : void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'User email')
            ->addArgument('password', InputArgument::REQUIRED, 'User password')
            ->addOption('role', null, InputOption::VALUE_OPTIONAL, 'User role', 'ROLE_ADMIN');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    : int {
        $email = $input->getArgument('email');
        $plainPassword = $input->getArgument('password');
        $role = $input->getOption('role');

        $existingUser = $this->entityManager
            ->getRepository(AdminUser::class)
            ->findOneBy(['email' => $email]);

        if ($existingUser) {
            $output->writeln('<error>User already exists!</error>');
            return Command::FAILURE;
        }

        $user = new AdminUser();
        $user->setEmail($email);
        $user->setRoles([$role]);

        $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $output->writeln('<info>Admin user created successfully!</info>');

        return Command::SUCCESS;
    }
}
