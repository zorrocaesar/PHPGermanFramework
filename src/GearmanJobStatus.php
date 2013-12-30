<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * GearmanJobStatus
 */
class GearmanJobStatus
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $status;

    /**
     * @var integer
     */
    private $percent;


    /**
     * Set id
     *
     * @param integer $id
     * @return GearmanJobStatus
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
     * Set status
     *
     * @param integer $status
     * @return GearmanJobStatus
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return integer 
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set percent
     *
     * @param integer $percent
     * @return GearmanJobStatus
     */
    public function setPercent($percent)
    {
        $this->percent = $percent;

        return $this;
    }

    /**
     * Get percent
     *
     * @return integer 
     */
    public function getPercent()
    {
        return $this->percent;
    }
}
