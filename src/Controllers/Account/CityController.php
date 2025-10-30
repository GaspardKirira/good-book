<?php

namespace Softadastra\Controllers\Account;

use Domain\Cities\CityRepository;
use Exception;
use Softadastra\Controllers\Controller;

class CityController extends Controller
{
    private $errors = 'errors.';

    public function cities(int $id)
    {
        try {
            $cityRepository = new CityRepository();
            $cities = $cityRepository->findByCountryId($id);

            $cityList = [];
            foreach ($cities as $city) {
                $cityList[] = [
                    'id' => $city->getId(),
                    'name' => $city->getName()
                ];
            }
            header('Content-Type: application/json');
            $this->json(['cities' => $cityList]);
        } catch (Exception $e) {
            return $this->errors($this->errors . 'errors', ['errorMessage' => $e->getMessage()]);
        }
    }
}
