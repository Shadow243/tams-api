<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Use this trait in a admin controller
 */
trait Crud
{
    /**
     * Get the model based on the class wich is defined in the controller
     * An example of a call is $this->model('find', 1) is the same as User::find(1)
     *
     * @return Builder
     */
    protected function model()
    {
        //	Validate the given model name
        $this->validateModel();

        //	Get all arguments given to this method
        $args = func_get_args();

        //	The first argument is the method name, who will be called statically
        $method = array_shift($args);

        if (! $method) {
            return new $this->model;
        }

        //	Execute the class method with the given arguments
        return call_user_func_array($this->model . '::' . $method, $args);
    }

    /**
     * Validate the model name which is specified in the controller
     *
     * @return void
     */
    protected function validateModel()
    {
        //	Check if the modelname is defined
        //	If not, throw an exception
        if (empty($this->model)) {
            throw new RuntimeException('No model defined in CRUD controller');
        }

        //	Check if the class exists
        //	If not, throw an exception
        if (! class_exists($this->model)) {
            throw new RuntimeException('Model with name ' . $this->model . ' not found');
        }

        //	TODO: Check if the class is a valid Eloquent Model
    }

    /**
     * Get an instance of a model by the given id
     *
     * @param  int  $id
     * @return Model
     */
    protected function getModelInstance($id)
    {
        if (is_object($id)) {
            return $this->instance = $id;
        }

        return $this->instance = $this->model('findOrFail', $id);
    }

    protected function getPayloadAttributes(array $payload)
    {
        $return = [];

        $attributes = Schema::getColumnListing($this->model()->getTable());

        foreach ($payload as $key => $value) {
            if ($key !== 'id' && in_array($key, $attributes)) {
                $return[$key] = $value;
            }
        }

        return $return;
    }
}
