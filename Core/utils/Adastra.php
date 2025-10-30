<?php

namespace Core\Utils;

class Adastra
{
    public static function checkRequired(array $fields): array
    {
        $required = [];
        foreach ($fields as $field) {
            if (empty($field)) {
                $required[] = $field;
            }
        }
        return $required;
    }

    public static function handleSuccess($message)
    {
        $response = ['success' => true, 'message' => $message];
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function handleError($message)
    {
        echo json_encode(['error' => $message], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
