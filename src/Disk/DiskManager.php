<?php

namespace Procket\Framework\Disk;

use Aws\S3\S3Client;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use League\Flysystem\AwsS3V3\PortableVisibilityConverter as AwsS3PortableVisibilityConverter;
use League\Flysystem\Filesystem as Flysystem;
use League\Flysystem\FilesystemAdapter as FlysystemAdapter;
use League\Flysystem\Ftp\FtpConnectionOptions;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\PhpseclibV3\SftpConnectionProvider;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;
use League\Flysystem\Visibility;
use Procket\Framework\Disk\Drivers\Ftp;
use Procket\Framework\Disk\Drivers\Local;
use Procket\Framework\Disk\Drivers\S3;
use Procket\Framework\Disk\Drivers\Sftp;

class DiskManager
{
    /**
     * The array of registered disk drivers
     * @var Flysystem[]
     */
    protected array $disks = [];

    /**
     * Get the registered disk driver
     *
     * @param string $name
     * @return Flysystem
     */
    public function disk(string $name): Flysystem
    {
        if (!isset($this->disks[$name])) {
            throw new InvalidArgumentException("Disk [$name] is not registered");
        }

        return $this->disks[$name];
    }

    /**
     * Register a disk driver instance
     *
     * @param string $name
     * @param array|Flysystem $configOrInst
     * @return Flysystem
     */
    public function register(string $name, array|Flysystem $configOrInst): Flysystem
    {
        if ($configOrInst instanceof Flysystem) {
            return $this->disks[$name] = $configOrInst;
        }

        if (empty($configOrInst['driver'])) {
            throw new InvalidArgumentException("Disk [$name] does not have a configured driver");
        }

        $driver = $configOrInst['driver'];
        $driverMethod = 'create' . ucfirst($driver) . 'Driver';

        if (!method_exists($this, $driverMethod)) {
            throw new InvalidArgumentException("Driver [$driver] is not supported");
        }

        return $this->disks[$name] = $this->{$driverMethod}($configOrInst);
    }

    /**
     * Create an instance of the local driver
     *
     * @param array $config
     * @return Flysystem
     */
    public function createLocalDriver(array $config): Flysystem
    {
        $visibility = PortableVisibilityConverter::fromArray(
            $config['permissions'] ?? [],
            $config['directory_visibility'] ?? $config['visibility'] ?? Visibility::PRIVATE
        );
        $links = ($config['links'] ?? null) === 'skip'
            ? LocalFilesystemAdapter::SKIP_LINKS
            : LocalFilesystemAdapter::DISALLOW_LINKS;
        $adapter = new Local(
            $config['root'], $visibility, $config['lock'] ?? LOCK_EX, $links
        );

        return $this->createFlysystem($adapter, $config);
    }

    /**
     * Create an instance of the ftp driver
     *
     * @param array $config
     * @return Flysystem
     */
    public function createFtpDriver(array $config): Flysystem
    {
        if (!isset($config['root'])) {
            $config['root'] = '';
        }

        $adapter = new Ftp(FtpConnectionOptions::fromArray($config));

        return $this->createFlysystem($adapter, $config);
    }

    /**
     * Create an instance of the sftp driver
     *
     * @param array $config
     * @return Flysystem
     */
    public function createSftpDriver(array $config): Flysystem
    {
        $provider = SftpConnectionProvider::fromArray($config);
        $root = $config['root'] ?? '/';
        $visibility = PortableVisibilityConverter::fromArray(
            $config['permissions'] ?? []
        );

        $adapter = new Sftp($provider, $root, $visibility);

        return $this->createFlysystem($adapter, $config);
    }

    /**
     * Create an instance of the Amazon S3 driver
     *
     * @param array $config
     * @return Flysystem
     */
    public function createS3Driver(array $config): Flysystem
    {
        $s3Config = $this->formatS3Config($config);
        $bucket = $s3Config['bucket'];
        $root = (string)($s3Config['root'] ?? '');
        $visibility = new AwsS3PortableVisibilityConverter(
            $config['visibility'] ?? Visibility::PUBLIC
        );
        $options = $config['options'] ?? [];
        $streamReads = $s3Config['stream_reads'] ?? false;
        $client = new S3Client($s3Config);

        $adapter = new S3(
            $client, $bucket, $root, $visibility, null, $options, $streamReads
        );

        return $this->createFlysystem($adapter, $config);
    }

    /**
     * Format the given S3 configuration with the default options
     *
     * @param array $config
     * @return array
     */
    protected function formatS3Config(array $config): array
    {
        $config += ['version' => 'latest'];

        if (!empty($config['key']) && !empty($config['secret'])) {
            $config['credentials'] = Arr::only($config, ['key', 'secret', 'token']);
        }

        return Arr::except($config, ['token']);
    }

    /**
     * Create a Flysystem instance with the given adapter
     *
     * @param FlysystemAdapter $adapter
     * @param array $config
     * @return Flysystem
     */
    protected function createFlysystem(FlysystemAdapter $adapter, array $config): Flysystem
    {
        return new Flysystem($adapter, Arr::only($config, [
            'directory_visibility',
            'disable_asserts',
            'temporary_url',
            'url',
            'visibility',
        ]));
    }
}