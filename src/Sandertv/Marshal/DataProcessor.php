<?php

declare(strict_types=1);

namespace Sandertv\Marshal;

class DataProcessor{

	/**
	 * put iterates through $data and sets values in $obj of properties that match keys in $data. The
	 * properties are only set if the types match with the current values of $obj.
	 *
	 * @param object $obj
	 * @param array  $data
	 */
	public function put(object $obj, array $data){
		foreach($data as $key => $value){
			if(!isset($obj->{(string) $key})){
				$obj->{(string) $key} = null;
			}
			$this->handleProperty($obj, (string) $key, $value);
		}
	}

	/**
	 * handleProperty handles a property in $obj with index $key. The value $value is the value the property should
	 * obtain, if their types are compatible.
	 *
	 * @param object $obj
	 * @param string $key
	 * @param mixed  $value
	 */
	private function handleProperty(object $obj, string $key, $value){
		if(is_object($obj->{$key})){
			if(!is_array($value)){
				// The property value was an object, but we didn't have an array as config value. We can't unmarshal
				// this at all, so just continue.
				return;
			}
			// Recursively iterate through the objects.
			$this->put($obj->{$key}, $value);

			return;
		}elseif(is_array($obj->{$key})){
			// We temporarily cast this array to an stdClass so we can use the put method for it.
			$v = (object) $obj->{$key};
			$this->put($v, $value);
			// Cast it back to an array to the types remain consistent.
			$obj->{$key} = (array) $v;

			return;
		}

		// Only set the value if the current value either matches the one in the configuration, or if the current
		// value is null.
		if(gettype($obj->{$key}) === gettype($value) || $obj->{$key} === null){
			$obj->{$key} = $value;
		}
	}
}