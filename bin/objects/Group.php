<?php

namespace unt\objects;

use unt\parsers\AttachmentsParser;
use unt\platform\Data;
use unt\platform\DataBaseManager;

class Group extends BaseObject
{
    const TYPE_OPEN = 0;
    const TYPE_CLOSED = 1;
    const TYPE_PERSONAL = 2;

    ///////////////////////////////////////

    protected int $id;

    protected string $title;
    protected string $description = '';
    protected string $status = '';
    protected string $website = '';

    protected int $city_id = 0;

    protected bool $isValid = false;

    protected ?Photo $photo = NULL;

    public function __construct(int $id)
    {
        parent::__construct();

        $group_info = $this->currentConnection->query('SELECT id, title, photo, description, status, website, city_id FROM groups.info WHERE id = :group_id LIMIT 1', [
            [':group_id', $id, \PDO::PARAM_INT]
        ], true);

        if ($group_info) {
            $this->isValid = true;

            $this->id = (int)$group_info['id'];
            $this->title = (string)$group_info['title'];
            $this->status = (string)$group_info['string'];
            $this->website = (string)$group_info['website'];

            $this->city_id = (int) $group_info['city_id'];

            $this->photo = (new AttachmentsParser())->getObject($group_info['photo']);
        }
    }

    public function valid(): bool
    {
        return $this->isValid;
    }

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return Photo|null
     */
    public function getPhoto(): ?Photo
    {
        return $this->photo;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param Photo|null $photo
     * @return Group
     */
    public function setPhoto(?Photo $photo): Group
    {
        $this->photo = $photo;
        return $this;
    }

    /**
     * @param string $title
     * @return Group
     */
    public function setTitle(string $title): Group
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @param string $status
     * @return Group
     */
    public function setStatus(string $status): Group
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @param string $description
     * @return Group
     */
    public function setDescription(string $description): Group
    {
        $this->description = $description;
        return $this;
    }

    public function addMember (int $user_id, int $access_level = 0): bool
    {
        if ($access_level < 0) $access_level = 0;
        if ($access_level > 7) $access_level = 7;

        $current_state = $this->currentConnection->query('SELECT in_group FROM groups.members WHERE group_id = :group_id AND user_id = :user_id LIMIT 1', [
            [':group_id', $this->getId(), \PDO::PARAM_INT],
            [':user_id',  $user_id,       \PDO::PARAM_INT]
        ], true);

        if (!isset($current_state['in_group'])) {
            return $this->currentConnection->prepare('INSERT INTO groups.members (user_id, group_id, in_group, access_level) VALUES (?, ?, ?, ?)')->execute([
                $user_id,
                $this->getId(),
                1,
                $access_level
            ]);
        } else {
            return $this->currentConnection->prepare('UPDATE groups.members SET in_group = 1, access_level = ? WHERE user_id = ? AND group_id = ? LIMIT 1')->execute([
                $user_id,
                $this->getId(),
                $access_level
            ]);
        }
    }

    public function removeMember (int $user_id): bool
    {
        $current_state = $this->currentConnection->query('SELECT in_group FROM groups.members WHERE group_id = :group_id AND user_id = :user_id LIMIT 1', [
            [':group_id', $this->getId(), \PDO::PARAM_INT],
            [':user_id',  $user_id,       \PDO::PARAM_INT]
        ], true);

        if (isset($current_state['in_group'])) {
            return $this->currentConnection->prepare('UPDATE groups.members SET in_group = 0, access_level = 0 WHERE user_id = ? AND group_id = ? LIMIT 1')->execute([
                $user_id,
                $this->getId(),
            ]);
        }

        return false;
    }

    public function apply(): bool
    {
        return false;
    }

    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'title' => $this->title,
            'status' => $this->status,
            'description' => $this->description
        ];

        if ($this->photo !== NULL)
            $data['photo'] = $this->photo->toArray();

        return $data;
    }

    //////////////////////////////////////////////////

    /**
     * @return array<Group>
     */
    public static function getList(int $offset = 0, int $count = 30): array
    {
        if ($offset <= 0) $offset = 0;
        if ($count <= 0) $count = 0;
        if ($count > 100) $count = 100;

        return array_filter(array_map(function ($item) {
            return new Group($item['group_id']);
        }, DataBaseManager::getConnection()->query('SELECT group_id FROM groups.members WHERE user_id = :user_id AND in_group = 1 LIMIT ' . $offset . ',' . $count, [
            [':user_id', intval($_SESSION['user_id']), \PDO::PARAM_INT]
        ])), function ($group) {
            return $group->valid();
        });
    }

    public static function create(string $title, string $description = '', int $type = self::TYPE_OPEN): ?Group
    {
        if (is_empty($title)) return NULL;

        if (strlen($title) > 64 || strlen($description) > 256) return NULL;

        if (!in_array($type, [self::TYPE_OPEN, self::TYPE_CLOSED, self::TYPE_PERSONAL])) return NULL;

        $res = DataBaseManager::getConnection()->prepare('INSERT INTO groups.info (owner_id, title, description, status, website, city_id) VALUES (?, ?, ?, ?, ?, ?);');

        if ($res->execute([
            intval($_SESSION['user_id']),
            $title,
            $description,
            '',
            '',
            0
        ])) {
            $res = DataBaseManager::getConnection()->prepare('SELECT LAST_INSERT_ID() AS group_id');
            if ($res->execute()) {
                $new_group_id = (int) $res->fetch(\PDO::FETCH_ASSOC)['group_id'];

                $group = new Group($new_group_id);

                if ($group->valid() && $group->addMember($_SESSION['user_id'], 7)) {
                    return $group;
                }
            }
        }

        return NULL;
    }

    public static function findById (int $id): ?Group
    {
        $group = new Group($id);

        if ($group->valid())
            return $group;

        return NULL;
    }
}

?>