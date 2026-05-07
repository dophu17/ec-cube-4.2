<?php

namespace Plugin\RemisePayment42\Entity;

/**
 * 自動継続課金API実行結果Entity
 *
 */
class RemiseACApiResult
{
    /**
     * result
     *
     */
    private $result;

    /**
     * result_data
     *
     */
    private $result_data;

    /**
     * error_level
     *
     */
    private $error_level;

    /**
     * error_message
     *
     */
    private $error_message;

    /**
     * Get result
     *
     * @return bool
     */
    public function isResult()
    {
        return $this->result;
    }

    /**
     * Set result
     *
     * @param  bool
     * @return RemiseACApiResult
     */
    public function setResult($result = null)
    {
        $this->result = $result;

        return $this;
    }

    /**
     * Get result_data
     *
     * @return object
     */
    public function getResultData()
    {
        return $this->result_data;
    }

    /**
     * Set result_data
     *
     * @param  object $result_data
     * @return RemiseACApiResult
     */
    public function setResultData($result_data = null)
    {
        $this->result_data = $result_data;
        return $this;
    }

    /**
     * Get error_level
     *
     * @return int
     */
    public function getErrorLevel()
    {
        return $this->error_level;
    }

    /**
     * Set error_level
     *
     * @param  int $error_level
     * @return RemiseACApiResult
     */
    public function setErrorLevel($error_level = null)
    {
        $this->error_level = $error_level;
        return $this;
    }

    /**
     * Get error_message
     *
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->error_message;
    }

    /**
     * Set error_message
     *
     * @param  string $error_message
     * @return RemiseACApiResult
     */
    public function setErrorMessage($error_message = null)
    {
        $this->error_message = $error_message;
        return $this;
    }
}
