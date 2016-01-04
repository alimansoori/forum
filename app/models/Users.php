<?php

/*
 +------------------------------------------------------------------------+
 | Phosphorum                                                             |
 +------------------------------------------------------------------------+
 | Copyright (c) 2013-2016 Phalcon Team and contributors                  |
 +------------------------------------------------------------------------+
 | This source file is subject to the New BSD License that is bundled     |
 | with this package in the file docs/LICENSE.txt.                        |
 |                                                                        |
 | If you did not receive a copy of the license and are unable to         |
 | obtain it through the world-wide-web, please send an email             |
 | to license@phalconphp.com so we can send you a copy immediately.       |
 +------------------------------------------------------------------------+
*/

namespace Phosphorum\Models;

use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Behavior\Timestampable;
use Phalcon\Mvc\Model\Resultset\Simple;

/**
 * Class Users
 *
 * @property Simple badges
 * @property Simple posts
 * @property Simple replies
 * @method Simple getBadges
 * @method Simple getPosts
 * @method Simple getReplies
 * @method static Users findFirstById(int $id)
 * @method static Users findFirstByLogin(string $login)
 * @method static Users findFirstByName(string $name)
 * @method static Users findFirstByEmail(string $email)
 * @method static Users findFirstByAccessToken(string $token)
 * @method static Users[] find($parameters=null)
 *
 * @package Phosphorum\Models
 */
class Users extends Model
{
    public $id;

    public $name;

    public $login;

    public $email;

    public $token_type;

    public $access_token;

    public $gravatar_id;

    public $created_at;

    public $modified_at;

    public $notifications;

    public $digest;

    public $timezone;

    public $moderator;

    public $karma;

    public $votes;

    public $votes_points;

    public $banned;

    public $theme;

    const SYSTEM_USER = 1;

    public function initialize()
    {
        $this->hasMany(
            'id',
            'Phosphorum\Models\UsersBadges',
            'users_id',
            [
                'alias' => 'badges',
                'reusable' => true
            ]
        );

        $this->hasMany(
            'id',
            'Phosphorum\Models\Posts',
            'users_id',
            [
                'alias' => 'posts',
                'reusable' => true
            ]
        );

        $this->hasMany(
            'id',
            'Phosphorum\Models\PostsReplies',
            'users_id',
            [
                'alias' => 'replies',
                'reusable' => true
            ]
        );

        $this->addBehavior(
            new Timestampable([
                'beforeCreate' => ['field' => 'created_at'],
                'beforeUpdate' => ['field' => 'modified_at']
            ])
        );
    }

    /**
     * @param $karma
     */
    public function increaseKarma($karma)
    {
        $this->karma += $karma;
        $this->votes_points += $karma;
    }

    /**
     * @param $karma
     */
    public function decreaseKarma($karma)
    {
        $this->karma -= $karma;
        $this->votes_points -= $karma;
    }

    public function beforeSave()
    {
        if (!trim($this->name)) {
            if ($this->login) {
                $this->name = $this->login;
            } else {
                $this->name = 'No Name';
            }
        }
    }

    public function beforeCreate()
    {
        $this->notifications = 'P';
        $this->digest        = 'Y';
        $this->moderator     = 'N';
        $this->karma        += Karma::INITIAL_KARMA;
        $this->votes_points += Karma::INITIAL_KARMA;
        $this->votes         = 0;
        $this->timezone      = 'Europe/London';
        $this->theme         = 'D';
        $this->banned        = 'N';
    }

    public function afterValidation()
    {
        if ($this->votes_points >= 50) {
            $this->votes++;
            $this->votes_points = 0;
        }
    }

    public function afterCreate()
    {
        if ($this->id > 0) {
            $activity           = new Activities();
            $activity->users_id = $this->id;
            $activity->type     = 'U';
            $activity->save();
        }
    }

    /**
     * @return string
     */
    public function getHumanKarma()
    {
        if ($this->karma >= 1000) {
            return sprintf("%.1f", $this->karma / 1000) . 'k';
        } else {
            return $this->karma;
        }
    }
}
