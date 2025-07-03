<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250703113721 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Permet aux appointments d\'être créés sans employé assigné';
    }

    public function up(Schema $schema): void
    {
        // Modifier la contrainte pour permettre que l'employé soit null
        $this->addSql('ALTER TABLE appointments ALTER COLUMN employee_id DROP NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // Remettre la contrainte NOT NULL (attention: cela peut échouer s'il y a des données avec employee_id null)
        $this->addSql('ALTER TABLE appointments ALTER COLUMN employee_id SET NOT NULL');
    }
}
