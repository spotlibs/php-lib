<?php

/**
 * PHP version 8
 *
 * @category Library
 * @package  Services
 * @author   Made Mas Adi Winata <m45adiwinata@gmail.com>
 * @license  https://mit-license.org/ MIT License
 * @version  GIT: 0.3.1
 * @link     https://github.com/spotlibs
 */

declare(strict_types=1);

namespace Spotlibs\PhpLib\Services;

/**
 * Metadata
 *
 * Metadata class to store mandatory context values
 *
 * @category StandardService
 * @package  Services
 * @author   Made Mas Adi Winata <m45adiwinata@gmail.com>
 * @license  https://mit-license.org/ MIT License
 * @link     https://github.com/spotlibs
 */
class Metadata
{
    public ?string $authorization;
    public ?string $user_agent;
    public ?string $cache_control;
    public ?string $api_key;
    public ?string $forwarded_for;
    public ?string $request_from;
    public ?string $device_id;
    public ?string $app;
    public ?string $version_app;
    public ?string $req_id;
    public ?string $task_id;
    public ?string $req_tags;
    public ?string $req_user;
    public ?string $req_nama;
    public ?string $req_kode_jabatan;
    public ?string $req_nama_jabatan;
    public ?string $req_kode_main_uker;
    public ?string $req_kode_region;
    public ?string $req_jenis_uker;
    public ?string $req_kode_uker;
    public ?string $req_nama_uker;
    public ?string $path_gateway;
    public ?string $identifier;
}
