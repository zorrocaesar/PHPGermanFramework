<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * GearmanItem
 */
class GearmanItem
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $in_progress;

    /**
     * @var integer
     */
    private $error;


    /**
     * Set id
     *
     * @param integer $id
     * @return GearmanItem
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set in_progress
     *
     * @param integer $inProgress
     * @return GearmanItem
     */
    public function setInProgress($inProgress)
    {
        $this->in_progress = $inProgress;

        return $this;
    }

    /**
     * Get in_progress
     *
     * @return integer 
     */
    public function getInProgress()
    {
        return $this->in_progress;
    }

    /**
     * Set error
     *
     * @param integer $error
     * @return GearmanItem
     */
    public function setError($error)
    {
        $this->error = $error;

        return $this;
    }

    /**
     * Get error
     *
     * @return integer 
     */
    public function getError()
    {
        return $this->error;
    }
    /**
     * @var integer
     */
    private $finished;


    /**
     * Set finished
     *
     * @param integer $finished
     * @return GearmanItem
     */
    public function setFinished($finished)
    {
        $this->finished = $finished;

        return $this;
    }

    /**
     * Get finished
     *
     * @return integer 
     */
    public function getFinished()
    {
        return $this->finished;
    }
}
