<?php
namespace App\Command;

use App\Entity\Role;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:init-roles',
    description: 'Initialise les rôles de base avec permissions',
)]
class InitRolesCommand extends Command
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();
        $this->em = $em;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $rolesData = [
            [
                'name' => 'Utilisateur',
                'slug' => 'ROLE_USER',
                'description' => 'Rôle utilisateur standard',
                'permissions' => json_encode(['order.create', 'order.view'])
            ],
            [
                'name' => 'Vendeur',
                'slug' => 'ROLE_SELLER',
                'description' => 'Rôle vendeur marketplace',
                'permissions' => json_encode(['product.create', 'product.edit', 'product.delete', 'order.view'])
            ],
            [
                'name' => 'Administrateur',
                'slug' => 'ROLE_ADMIN',
                'description' => 'Rôle administrateur',
                'permissions' => json_encode(['*'])
            ],
        ];

        foreach ($rolesData as $data) {
            $role = $this->em->getRepository(Role::class)->findOneBy(['slug' => $data['slug']]);
            if (!$role) {
                $role = new Role();
                $role->setName($data['name'])
                    ->setSlug($data['slug'])
                    ->setDescription($data['description'])
                    ->setPermissions($data['permissions'])
                    ->setCreatedAt(new \DateTimeImmutable());
                $this->em->persist($role);
                $output->writeln("<info>Rôle {$data['slug']} créé.</info>");
            } else {
                $output->writeln("<comment>Rôle {$data['slug']} déjà existant.</comment>");
            }
        }
        $this->em->flush();
        $output->writeln('<info>Initialisation des rôles terminée.</info>');
        return Command::SUCCESS;
    }
}
