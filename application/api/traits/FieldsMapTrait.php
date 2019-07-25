<?php
/**
 * Created by PhpStorm.
 * User: haichang
 * Date: 2019-05-03
 * Time: 16:38
 */

namespace app\api\traits;


trait FieldsMapTrait
{
    protected $fieldsMap = [];

    public function fieldsMap(array $map=[], array $data=[])
    {
        if (count($map) == 0) {
            $map = $this->fieldsMap;
        }

        if (count($data) == 0) {
            $data = $this->data;
        }

        if (empty($data)) {
            return $this;
        }

        foreach ($data as $field => $value) {
            if (isset($map[$field])) {
                $data[$map[$field]] = $value;
                unset($data[$field]);
            }
        }

        $this->data = $data;
        return $this;
    }
}