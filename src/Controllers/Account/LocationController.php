<?php

namespace Softadastra\Controllers\Account;

use Domain\Location\Location;
use Domain\Location\LocationRepository;
use Exception;
use Softadastra\Controllers\Controller;
use Softadastra\Model\GetUser;

class LocationController extends Controller
{
    public function getLocationAPI()
    {
        $getUser = new GetUser();
        $user = $getUser->getUserEntity();

        if ($user) {
            $locationRepository = new LocationRepository();
            $location = $locationRepository->findByUserId($user->getId());

            if ($location) {
                $this->json([
                    'user_id' => $location->getUserId() ?? 'N/A',
                    'country_id' => $location->getCountryId() ?? 'N/A',
                    'city_id' => $location->getCityId() ?? 'N/A',
                    'show_city' => $location->getShowCity() ?? 'N/A'
                ]);
            } else {
                $this->json([
                    'error' => 'No location details found for the user.'
                ], 404);
            }
        } else {
            $this->json([
                'error' => 'User not found or token is invalid. Please log in.',
            ], 401);
        }
    }

    public function createLocation()
    {
        $getUser = new GetUser();
        $user = $getUser->getUserEntity();

        if (!$user) {
            $this->json(['error' => "User not found or token is invalid. Please log in."], 401);
        }

        try {
            $country_id = $_POST['country_id'] ?? 1;
            $city_id = $_POST['city_id'] ?? 1;
            $show_city = isset($_POST['show_city']) ? 1 : 0;
            error_log('show_city create(): ' . $_POST['show_city']);

            $location = new Location($user->getId(), $city_id, $country_id, $show_city);

            $locationRepository = new LocationRepository();
            $locationUpdated = $locationRepository->save($location);

            if ($locationUpdated) {
                $response = [
                    'success' => true,
                    'message' => "Location saved successfully."
                ];
                $this->json($response, 200);
            } else {
                $response = [
                    'error' => 'Failed to create location. Please try again later.'
                ];
                $this->json($response, 500);
            }
        } catch (Exception $e) {
            $this->json([
                'error' => 'An error occurred while updating the location. Please try again later.'
            ], 500);
        }
    }

    public function updateLocation()
    {
        $getUser = new GetUser();
        $user = $getUser->getUserEntity();

        if (!$user) {
            $this->json(['error' => "User not found or token is invalid. Please log in."], 401);
        }

        try {
            $country_id = $_POST['country_id'] ?? 1;
            $city_id = $_POST['city_id'] ?? 1;
            $show_city = isset($_POST['show_city']) ? (int) $_POST['show_city'] : 0;

            $location = new Location($user->getId(), $city_id, $country_id, $show_city);
            $locationRepository = new LocationRepository();
            $locationUpdated = $locationRepository->update($location);

            if ($locationUpdated) {
                $response = [
                    'success' => true,
                    'message' => "Location updated successfully."
                ];
                $this->json($response, 200);
            } else {
                $response = [
                    'error' => 'Failed to update location. Please try again later.'
                ];
                $this->json($response, 500);
            }

            $this->json($response);
        } catch (Exception $e) {
            $this->json([
                'error' => 'An error occurred while updating the location. Please try again later.'
            ]);
            $this->json($response, 500);
        }
    }
}
