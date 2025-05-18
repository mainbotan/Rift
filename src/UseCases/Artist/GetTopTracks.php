<?php

namespace Rift\UseCases\Artist;

use Rift\Core\Contracts\Response;
use Rift\Core\Contracts\ResponseDTO;
use Rift\Core\UseCases\UseCaseInterface;

class GetTopTracks extends Response implements UseCaseInterface {

    public function execute(array $data): ResponseDTO
    {   
        return self::response(
            [
                [
                    'id' => 1,
                    'name' => 'hui_1',
                    'artists' => [],
                    'primary_artist' => [
                        'id' => $data['id'],
                        'name' => 'HUI'
                    ],
                    'lirycs' => true
                ],
                [
                    'id' => 2,
                    'name' => 'hui_2',
                    'artists' => [],
                    'primary_artist' => [
                        'id' => $data['id'],
                        'name' => 'HUI'
                    ],
                    'lirycs' => true
                ]
            ],
            self::HTTP_OK
        );
    }
}
