<?php

namespace Procket\Framework\Database\Migration;

use Illuminate\Database\Migrations\MigrationCreator as BaseMigrationCreator;
use Illuminate\Filesystem\Filesystem;
use Procket\Framework\ClassPropertiesAware;

/**
 * Migration creator
 */
class MigrationCreator extends BaseMigrationCreator
{
    use ClassPropertiesAware;

    /**
     * Migration class stub
     * @var string
     */
    public string $stub = <<<'STUB'
<?php

use Procket\Framework\Database\Migration\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
	/**
	 * @inheritDoc
	 */
    public function up()
    {
        //
    }

	/**
	 * @inheritDoc
	 */
    public function down()
    {
        //
    }
};
STUB;

    /**
     * Migration create stub
     * @var string
     */
    public string $createStub = <<<'CREATE_STUB'
<?php

use Procket\Framework\Database\Migration\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
	/**
	 * @inheritDoc
	 */
    public function up()
    {
        $this->schema()->create('{{ table }}', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });
    }

	/**
	 * @inheritDoc
	 */
    public function down()
    {
        $this->schema()->dropIfExists('{{ table }}');
    }
};
CREATE_STUB;

    /**
     * Migration update stub
     * @var string
     */
    public string $updateStub = <<<'UPDATE_STUB'
<?php

use Procket\Framework\Database\Migration\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
	/**
	 * @inheritDoc
	 */
    public function up()
    {
        $this->schema()->table('{{ table }}', function (Blueprint $table) {
            //
        });
    }

	/**
	 * @inheritDoc
	 */
    public function down()
    {
        $this->schema()->table('{{ table }}', function (Blueprint $table) {
            //
        });
    }
};
UPDATE_STUB;

    /**
     * Create a new migration creator instance
     *
     * @param Filesystem $filesystem Filesystem instance
     * @param array $options class public properties
     */
    public function __construct(Filesystem $filesystem, array $options = [])
    {
        parent::__construct($filesystem, null);

        $this->setClassOptions($options);
    }

    /**
     * Get the migration stub
     *
     * @param string|null $table
     * @param bool $create
     * @return string
     */
    protected function getStub($table, $create): string
    {
        if (is_null($table)) {
            $stub = $this->stub;
        } elseif ($create) {
            $stub = $this->createStub;
        } else {
            $stub = $this->updateStub;
        }

        return $stub;
    }

    /**
     * Get the path to the stubs
     *
     * @return null
     */
    public function stubPath()
    {
        return null;
    }
}