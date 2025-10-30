<?php

namespace Softadastra\Controllers\Account;

use Domain\Countries\CountryRepository;
use Exception;
use Softadastra\Controllers\Controller;

class CountryController extends Controller
{
    private $errors = 'errors.';

    public function countries()
    {
        try {
            $countryRepository = new CountryRepository();
            $countrys = iterator_to_array($countryRepository->findAll());

            $countryList = [];
            foreach ($countrys as $country) {
                $countryList[] = [
                    'id' => $country->getId(),
                    'name' => $country->getName()
                ];
            }
            $this->json(['countries' => $countryList]);
        } catch (Exception $e) {
            return $this->errors($this->errors . 'errors', ['errorMessage' => $e->getMessage()]);
        }
    }
}
