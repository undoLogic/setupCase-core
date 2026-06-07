### Install SETUPCASE


    public function bootstrap(): void
    {
        require_once $this->configDir . 'bootstrap.php';
        if (file_exists($this->configDir . 'bootstrap-setupCase.php')) {
            require_once $this->configDir . 'bootstrap-setupCase.php';
        }
        require_once $this->configDir . 'bootstrap-setupCase.php';
    }