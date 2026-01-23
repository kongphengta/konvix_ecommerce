<?php
namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class PermissionChecker
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Vérifie si l'utilisateur possède la permission donnée
     */
    public function hasPermission(User $user, string $permission): bool
    {
        foreach ($user->getRoles() as $roleSlug) {
            $role = $this->em->getRepository('App:Role')->findOneBy(['slug' => $roleSlug]);
            if ($role) {
                $permissions = json_decode($role->getPermissions(), true) ?? [];
                if (in_array($permission, $permissions) || in_array('*', $permissions)) {
                    return true;
                }
            }
        }
        return false;
    }
}
