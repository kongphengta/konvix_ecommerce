<?php
namespace App\Service;

use Vich\UploaderBundle\Mapping\PropertyMapping;
use Vich\UploaderBundle\Naming\DirectoryNamerInterface;

class ProductImageDirectoryNamer implements DirectoryNamerInterface
{
    public function directoryName($object, PropertyMapping $mapping): string
    {
        // Utilise le dossier du produit, ou vide si non défini
        return $object->getImageFolder() ?? '';
    }
}
