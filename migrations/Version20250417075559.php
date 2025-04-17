<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250417075559 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE sortie DROP FOREIGN KEY FK_3C3FD3F2D5E86FF
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE etat
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX UNIQ_D79F6B1186CC499D ON participant
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE participant DROP pseudo
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_3C3FD3F2D5E86FF ON sortie
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE sortie ADD etat VARCHAR(20) NOT NULL, DROP etat_id, DROP image
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE etat (id INT AUTO_INCREMENT NOT NULL, libelle VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = '' 
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE participant ADD pseudo VARCHAR(50) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_D79F6B1186CC499D ON participant (pseudo)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE sortie ADD etat_id INT NOT NULL, ADD image VARCHAR(255) DEFAULT NULL, DROP etat
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE sortie ADD CONSTRAINT FK_3C3FD3F2D5E86FF FOREIGN KEY (etat_id) REFERENCES etat (id) ON UPDATE NO ACTION ON DELETE NO ACTION
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_3C3FD3F2D5E86FF ON sortie (etat_id)
        SQL);
    }
}
