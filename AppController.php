<?php
declare(strict_types=1);

namespace App\Controller;

use App\Traits\RequestTrait;
use Cake\Controller\Controller;
use Cake\Core\Configure;
use stdClass;

/**
 * Application Controller
 */
class AppController extends Controller
{
    use RequestTrait;

    /**
     * Redirect
     *
     * @var null
     */
    public $redirect = null;

    /**
     * Is production
     *
     * @var bool
     */
    public $isProduction = false;

    /**
     * Initialization hook method.
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->setIsProduction();
        $this->checkRedirections();
    }

    /**
     * Get redirection
     *
     * @return mixed
     */
    public function getRedirect()
    {
        return $this->redirect;
    }

    /**
     * Set redirection
     *
     * @param mixed $data Redirect data.
     * @return void
     */
    public function setRedirect($data): void
    {
        $this->redirect = $data;
    }

    /**
     * Check redirection
     *
     * @return void
     */
    public function checkRedirections(): void
    {
        $url = $this->getRequestTarget();
        if ($url) {
            $this->loadModel('Redirections');
            $redirection = $this->Redirections->find('all')->where(['redirection_from' => $url])->first();
            if (!empty($redirection)) {
                $this->setRedirect($redirection->redirect_to);
            }
        }
    }

    /**
     * Set is production
     *
     * @return void Sets $isProduction for front end use.
     */
    private function setIsProduction(): void
    {
        if (Configure::read('Website.is_production') !== null) {
            $this->isProduction = Configure::read('Website.is_production');
        }
    }

    /**
     * Static is production
     *
     * @return bool
     */
    public static function isProduction(): bool
    {
        return (new self())->isProduction;
    }

    /**
     * Array to object converter
     *
     * @param array|null $array   Array to conversion.
     * @param array|null $options Additional options.
     * @return \stdClass
     */
    public function arrayToObject($array, $options): stdClass
    {
        $object = new stdClass();

        $emptyValues = $options['emptyValues'] ?? false;
        $defaultValues = $options['defaultValues'] ?? false;

        if (!empty($array) && is_array($array)) {
            foreach ($array as $key => $value) {
                if ($emptyValues) {
                    $object->$value = '';
                } elseif ($defaultValues) {
                    $object->$value = $defaultValues[$value] ?? '';
                } else {
                    $object->$key = $value;
                }
            }
        }

        return $object;
    }

    /**
     * Generate validation errors
     *
     * @param array $validationErrors Validation errors.
     * @return array
     */
    public function generateValidationErrors(array $validationErrors): array
    {
        $errors = [];
        foreach ($validationErrors as $key => $error) {
            foreach ($error as $errorMessage) {
                $errors[$key] = $errorMessage;
            }
        }

        return $errors;
    }
}
