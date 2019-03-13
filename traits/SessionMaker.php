<?php namespace Initbiz\PowerComponents\Traits;

use Str;
use Session;
use SystemException;

trait SessionMaker
{
    public $sessionId;

    protected function putSession($key, $value)
    {
        $sessionId = $this->makeSessionId();

        $currentStore = $this->getSession();

        $currentStore[$key] = $value;

        Session::put($this->sessionId, base64_encode(serialize($currentStore)));
    }

    /**
     * Retrieves a widget related key/value pair from session data.
     * @param string $key Unique key for the data store.
     * @param string $default A default value to use when value is not found.
     * @return string
     */
    protected function getSession($key = null, $default = null)
    {
        $this->sessionId = $this->makeSessionId();

        $currentStore = [];

        if (
            Session::has($this->sessionId) &&
            ($cached = @unserialize(@base64_decode(Session::get($this->sessionId)))) !== false
        ) {
            $currentStore = $cached;
        }

        if ($key === null) {
            return $currentStore;
        }

        return isset($currentStore[$key]) ? $currentStore[$key] : $default;
    }

    /**
     * Returns a unique session identifier for this widget and controller action.
     * @return string Session ID
     */
    protected function makeSessionId()
    {
        $component = property_exists($this, 'component') && $this->component
            ? $this->component
            : $this;

        $rootNamespace = Str::getClassId(Str::getClassNamespace(Str::getClassNamespace($component)));

        $sessionId = 'frontendwidget.' . $rootNamespace . '-' . class_basename($component) . '-' . $component->alias;
        return $sessionId;
    }

    /**
     * Resets all session data related to this widget.
     * @return void
     */
    public function resetSession()
    {
        $this->sessionId = $this->makeSessionId();

        Session::forget($this->sessionId);
    }
}
