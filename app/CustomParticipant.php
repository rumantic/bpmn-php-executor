<?php

namespace App;

use PHPMentors\Workflower\Workflow\Participant\ParticipantInterface;
use PHPMentors\Workflower\Workflow\Resource\ResourceInterface;

class CustomParticipant implements ParticipantInterface
{
    private $roles = [];
    /**
     * @var ResourceInterface
     */
    private $resource;

    public function __construct(array $roles = [])
    {
        $this->roles = $roles;
    }

    public function hasRole($role)
    {
        return in_array($role, $this->roles, true);
    }

    public function addRole($role)
    {
        if (!in_array($role, $this->roles, true)) {
            $this->roles[] = $role;
        }
    }

    public function getRoles()
    {
        return $this->roles;
    }

    public function getId()
    {
        return 'custom';
    }

    public function setResource(ResourceInterface $resource)
    {
        $this->resource = $resource;
    }

    public function getResource()
    {
        return $this->resource;
    }

    public function getName()
    {
        return 'custom';
    }
}
