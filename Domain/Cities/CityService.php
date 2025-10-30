<?php

namespace Domain\Cities;

use Exception;
use Softadastra\Application\Utils\FlashMessage;

class CityService
{
    private $repository;

    public function __construct(CityRepository $repository)
    {
        $this->repository = $repository;
    }

    public function create($name)
    {
        try {
            $name = $name ?? '';
            $country_id = $country_id ?? '';

            if ($this->repository->findByName($name)) {
                return $this->handleError("This name is already taken.");
            }

            $entity = new City($name, $country_id);

            $errors = CityValidator::validate($entity);

            if (!empty($errors)) {
                return $this->handleError($errors);
            }

            $this->repository->save($entity);

            return $this->handleSuccess("City created successfully");
        } catch (Exception $e) {
            return $this->handleError("An error occurred.");
        }
    }

    private function handleSuccess($message, $entity = null)
    {
        $response = ['success' => true, 'message' => $message];
        if ($entity) {
            $response['entity'] = [
                'name' => $entity->getName()
            ];
        }
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function handleError($message)
    {
        echo json_encode(['error' => $message], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
