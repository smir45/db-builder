<?php

namespace Ryzen\DbBuilder;

interface DbInterface
{
    public function get($type = null, $argument = null);

    public function getAll($type = null, $argument = null);

    public function update(array $data, $type = false);

    public function insert(array $data, $type = false);

    public function delete($type = false);
}