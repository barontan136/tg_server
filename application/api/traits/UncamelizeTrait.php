<?php
/**
 * Created by PhpStorm.
 * User: haichang
 * Date: 2019-05-03
 * Time: 17:37
 */

namespace app\api\traits;


trait UncamelizeTrait
{
    public function uncamelize(array $data=[])
    {
        if (count($data) == 0) {
            $data = $this->data;
        }

        if (empty($data)) {
            return $this;
        }

        foreach ($data as $field => $value) {
            $data[uncamelize($field)] = $value;
        }

        $this->data = $data;
        return $this;
    }
}