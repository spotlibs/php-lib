<?php

/**
 * PHP version 8
 *
 * @category Library
 * @package  Libraries
 * @author   Made Mas Adi Winata <m45adiwinata@gmail.com>
 * @license  https://mit-license.org/ MIT License
 * @version  GIT: 0.3.7
 * @link     https://github.com/spotlibs
 */

declare(strict_types=1);

namespace Spotlibs\PhpLib\Libraries;

/**
 * MapRoute
 *
 * Name for HTTP Client timeout unit
 *
 * @category HttpClient
 * @package  Client
 * @author   Made Mas Adi Winata <m45adiwinata@gmail.com>
 * @license  https://mit-license.org/ MIT License
 * @link     https://github.com/spotlibs
 */
class MapRoute
{
    public int $id;
    public string $target_url;
    public string $mock_url;
    public bool $flag;

    /**
     * Create MapRoute instance
     *
     * @param array $data maproute data
     */
    public function __construct(array $data)
    {
        $this->id = $data['id'];
        $this->target_url = $data['target_url'];
        $this->mock_url = $data['mock_url'];
        $this->flag = $data['flag'];
    }
}
