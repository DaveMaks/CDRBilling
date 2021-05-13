<?php

/**
 * Created by PhpStorm.
 * User: vanuykov.m
 * Date: 09.12.2020
 * Time: 23:51
 */

namespace App\Validator;

use Phalcon\Messages\Message;
use Phalcon\Validation;
use Phalcon\Validation\Exception;


class MimeTypeUpload extends \Phalcon\Validation\Validator\File\AbstractFile
{

    protected $template = "File :field must be of type: :types";


    public function validate(\Phalcon\Validation $validation, $field): bool
    {
        if ($this->checkUpload($validation, $field) === false) {
            return false;
        }
        $value = $validation->getValue($field);
        $types = $this->getOption("types");
        try {
            if (!preg_match('/\.\w+$/i', $value['name'], $matches, PREG_OFFSET_CAPTURE))
                throw new Exception();

            if (!in_array($matches[0][0], $types))
                throw new Exception();

        } catch (\Exception $ex) {

            $replacePairs = [
                ":types" => join(", ", $types)
            ];
            $validation->appendMessage(
                $this->messageFactory($validation, $field, $replacePairs));
            return false;
        }
        return true;
    }
}