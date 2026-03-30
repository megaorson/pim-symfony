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
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsCommand(name: 'app:create-admin')]
final class CreateAdminCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly TranslatorInterface $translator
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription($this->translator->trans('console.create_admin.description'))
            ->addArgument('email', InputArgument::REQUIRED, $this->translator->trans('console.create_admin.argument.email'))
            ->addArgument('password', InputArgument::REQUIRED, $this->translator->trans('console.create_admin.argument.password'))
            ->addOption('role', null, InputOption::VALUE_OPTIONAL, $this->translator->trans('console.create_admin.option.role'), 'ROLE_ADMIN');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = (string) $input->getArgument('email');
        $plainPassword = (string) $input->getArgument('password');
        $role = (string) $input->getOption('role');

        $existingUser = $this->entityManager->getRepository(AdminUser::class)->findOneBy(['email' => $email]);

        if ($existingUser) {
            $output->writeln('<error>' . $this->translator->trans('console.create_admin.error.exists') . '</error>');
            return Command::FAILURE;
        }

        $user = new AdminUser();
        $user->setEmail($email);
        $user->setRoles([$role]);
        $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $output->writeln('<info>' . $this->translator->trans('console.create_admin.success') . '</info>');

        return Command::SUCCESS;
    }
}
