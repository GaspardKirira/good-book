<?php

namespace Domain\VendorShippingOptions;

use App\Response\JsonResponse;
use Exception;
use Softadastra\Application\Image\ImageGenerator;

class VendorShippingOptionService
{
    public function __construct(private VendorShippingOptionRepository $repository) {}

    public function create(array $post, array $files): void
    {
        try {
            $name        = trim($post['name'] ?? '');
            $description = trim($post['description'] ?? '');
            $address     = trim($post['address'] ?? '');
            $imageFile   = $files['image'] ?? null;

            $errors = [];
            if (mb_strlen($name) < 2)        $errors['name'] = 'Name too short (min 2).';
            if (mb_strlen($address) < 2)     $errors['address'] = 'Address too short.';
            if (mb_strlen($description) < 3) $errors['description'] = 'Description too short.';
            if ($errors) JsonResponse::validationError($errors);

            if ($this->repository->findByName($name)) {
                JsonResponse::handleError("This name is already taken.", 409);
            }

            $imgName = null;
            if ($imageFile && !empty($imageFile['tmp_name'])) {
                $imgName = (new ImageGenerator())->photo($imageFile, 'vendor', 'images/vendor/');
            }

            $entity = new VendorShippingOption($imgName, $name, $address, $description);

            $vErr = VendorShippingOptionValidator::validate($entity);
            if (!empty($vErr)) {
                JsonResponse::validationError($vErr);
            }

            $this->repository->save($entity);

            JsonResponse::created(
                [
                    'vendorShippingOption' => [
                        'id'          => $entity->getId(),
                        'name'        => $entity->getName(),
                        'address'     => $entity->getAddress(),
                        'description' => $entity->getDescription(),
                        'image'       => $entity->getImage(),
                    ]
                ],
                'VendorShippingOption created successfully.'
            );
        } catch (Exception $e) {
            JsonResponse::serverError("An error occurred : " . $e->getMessage());
        }
    }
}
