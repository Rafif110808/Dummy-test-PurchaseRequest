<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePurchaseRequestTables extends Migration
{
    public function up()
    {
        // Create Purchase Request Header Table
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'auto_increment' => true,
            ],
            'transcode' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
            ],
            'transdate' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'supplierid' => [
                'type' => 'INT',
                'null' => true,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'createdby' => [
                'type' => 'INT',
                'null' => true,
            ],
            'createddate' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            'updatedby' => [
                'type' => 'INT',
                'null' => true,
            ],
            'updateddate' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            'isactive' => [
                'type' => 'BOOLEAN',
                'default' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('trpurchaserequesthd');

        // Create Purchase Request Detail Table
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'auto_increment' => true,
            ],
            'headerid' => [
                'type' => 'INT',
                'null' => true,
            ],
            'productid' => [
                'type' => 'INT',
                'null' => true,
            ],
            'uomid' => [
                'type' => 'INT',
                'null' => true,
            ],
            'qty' => [
                'type' => 'DECIMAL',
                'constraint' => '18,3',
                'null' => true,
            ],
            'createdby' => [
                'type' => 'INT',
                'null' => true,
            ],
            'createddate' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            'updatedby' => [
                'type' => 'INT',
                'null' => true,
            ],
            'updateddate' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            'isactive' => [
                'type' => 'BOOLEAN',
                'default' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('trpurchaserequestdt');
    }

    public function down()
    {
        // Drop tables in reverse order
        $this->forge->dropTable('trpurchaserequestdt');
        $this->forge->dropTable('trpurchaserequesthd');
    }
}
