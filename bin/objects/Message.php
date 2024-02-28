<?php

namespace unt\objects;

use PDO;
use unt\parsers\AttachmentsParser;
use unt\platform\Data;
use unt\platform\DataBaseManager;

/**
 * Message class
 * Represents the message object
 */

class Message
{
    private bool $isValid;

    protected $boundChat;
    protected $currentConnection;

    private bool $is_edited;
    private bool $deletedForAll;

    private string $text;
    private int    $time;
    private int    $owner_id;
    private int    $id;

    private bool $isService = false;

    private $fwd;
    private $attachments;

    public function __construct (Chat $chat, int $messageId)
    {
        $this->isValid           = false;
        $this->currentConnection = DataBaseManager::getConnection();

        if ($chat->valid())
        {
            $uid = $chat->getUID();

            $res = $this->currentConnection->prepare("SELECT deleted_for_all, deleted_for, uid, local_chat_id, is_edited, time, text, event, new_src, new_title, owner_id, to_id, reply, attachments FROM messages.chat_engine_1 WHERE uid = ? AND local_chat_id = ? AND deleted_for_all = 0 LIMIT 1");

            if ($res->execute([$uid, $messageId]))
            {
                $data = $res->fetch(PDO::FETCH_ASSOC);
                if ($data)
                {
                    $this->isValid   = true;
                    $this->boundChat = $chat;
                    $this->id        = $messageId;

                    if (!is_empty($data['event']))
                    {
                        $this->event     = strval($data['event']);
                        $this->isService = true;

                        $this->eventInfo = new Data([]);
                        if (!is_empty($data['new_title']) || !is_empty($data['new_src']))
                        {
                            if (!is_empty($data['new_title']))
                            {
                                $this->eventInfo->newTitle = strval($data['new_title']);
                            }
                            if (!is_empty($data['new_src']))
                            {
                                $this->eventInfo->newSrc = strval($data['new_src']);
                            }
                        }

                        if (intval($data['to_id']) !== 0)
                        {
                            $this->eventInfo->actionerId = intval($data['to_id']);
                        }

                        $this->fwd         = [];
                        $this->attachments = [];
                    } else
                    {
                        $this->text        = strval($data['text']);
                        $this->is_edited   = boolval(intval($data['is_edited']));
                        $this->attachments = (new AttachmentsParser())->getObjects($data['reply']);
                        $this->fwd         = ForwardedMessage::getList($data['attachments']);
                    }

                    $this->time          = intval($data['time']);
                    $this->owner_id      = intval($data['owner_id']);
                    $this->deletedForAll = boolval(intval($data['deleted_for_all']));
                    $this->deletedFor    = explode(',', trim($data['deleted_for']));
                }
            }
        }
    }

    public function isServiceMessage (): bool
    {
        return $this->isService;
    }

    public function valid (): bool
    {
        return $this->isValid;
    }

    public function isEdited (): bool
    {
        return $this->is_edited;
    }

    public function getId (): int
    {
        return $this->id;
    }

    public function isDeleted (): bool
    {
        if ($this->deletedForAll) return true;

        foreach ($this->deletedFor as $index => $user_id) {
            if (intval($user_id) === intval($_SESSION['user_id'])) return true;
        }

        return false;
    }

    public function getAttachments (): array
    {
        return $this->attachments;
    }

    public function getForwarded (): array
    {
        return $this->fwd;
    }

    public function setText (string $text): bool
    {
        if (is_empty($text) || strlen($text) > 4096) return false;

        $this->text = $text;
        return true;
    }

    public function setForwarded (array $fwd = []): bool
    {
        if (count($fwd) > 1000) return false;

        foreach ($fwd as $index => $message) {
            if (!($message instanceof ForwardedMessage)) return false;
            if (!$message->valid()) return false;
        }

        $this->fwd = $fwd;
        return true;
    }

    public function setAttachments (array $attachmentsList): bool
    {
        if (count($attachmentsList) > 10) return false;

        foreach ($attachmentsList as $index => $attachment) {
            if (!($attachment instanceof Attachment)) return false;

            if (!$attachment->valid()) return false;
        }

        $this->attachments = $attachmentsList;
        return true;
    }

    public function delete ($deleteForAll = false): bool
    {
        if (!$deleteForAll)
        {
            $this->deletedFor[] = intval($_SESSION['user_id']);

            if ($this->currentConnection->prepare("UPDATE messages.chat_engine_1 SET deleted_for = ? WHERE local_chat_id = ? AND uid = ? LIMIT 1")->execute([trim(implode(',', $this->deletedFor)), $this->getId(), $this->boundChat->getUID()]))
            {
                return true;
            }
        } else
        {
            if ($this->boundChat->getType() === 'dialog')
            {
                if ($this->getOwnerId() !== intval($_SESSION['user_id'])) return false;
            }
            if ($this->boundChat->getType() === 'conversation')
            {
                if ($this->getOwnerId() !== intval($_SESSION['user_id']))
                {
                    if ($this->boundChat->getPermissions()->delete_messages_2 > $this->boundChat->getAccessLevel()) return false;
                }
            }

            $this->deletedForAll = true;

            if ($this->currentConnection->prepare("UPDATE messages.chat_engine_1 SET deleted_for_all = 1 WHERE local_chat_id = ? AND uid = ? LIMIT 1")->execute([$this->getId(), $this->boundChat->getUID()]))
            {
                return true;
            }
        }

        return false;
    }

    public function apply (): int
    {
        if ($this->getOwnerId() !== intval($_SESSION['user_id'])) return 0;

        $can_write_messages = $this->boundChat->canWrite();

        if ($can_write_messages === 0) return -1;
        if ($can_write_messages === -1) return -2;
        if ($this->boundChat->getType() === 'conversation' && $can_write_messages === -2)
        {
            if (!$this->boundChat->addUser(intval($_SESSION['user_id']))) return -1;
        }

        $attachments_list = $this->getAttachments();
        $text             = $this->getText();

        if (is_empty($text) && count($attachments_list) === 0) return -3;
        if (strlen($text) > 4096) return 0;
        if (count($attachments_list) > 10) return -5;

        $done_text    = trim($text);
        $current_time = $this->getTime();
        $done_atts    = '';

        foreach ($attachments_list as $key => $value) {
            $done_atts .= $value->getCredentials();
        }

        $companion_id = $this->boundChat->getType() === 'dialog' ? ($this->boundChat->getCompanion()->getType() === 'bot' ? $this->boundChat->getCompanion()->getId() * -1 : $this->boundChat->getCompanion()->getId()) : 0;

        $res = $this->currentConnection->prepare("UPDATE messages.chat_engine_1 SET text = ?, attachments = ?, reply = ?, is_edited = 1 WHERE uid = ? AND local_chat_id = ? LIMIT 1");

        if (!$res->execute([
            $done_text,
            '',
            $done_atts,
            $this->boundChat->getUID(),
            $this->getId()
        ])) return -9;

//        $atts_array = [];
//        foreach ($attachments_list as $index => $attachment)
//        {
//            $atts_array[] = $attachment->toArray();
//        }
//
//        $event = [
//            'event' => 'edit_message',
//            'message' => [
//                'from_id'     => $this->getOwnerId(),
//                'id'          => $this->getId(),
//                'type'        => 'message',
//                'text'        => $done_text,
//                'time'        => $this->getTime(),
//                'attachments' => $atts_array,
//                'fwd'         => [],
//                'is_edited'   => 1
//            ],
//            'uid' => $this->boundChat->getUID()
//        ];
//
//        $user_ids  = [];
//        $local_ids = [];
//
//        if ($this->boundChat->getType() === 'dialog')
//        {
//            if (intval($_SESSION['user_id']) === $this->boundChat->getCompanion()->getId())
//            {
//                $user_ids  = [intval($_SESSION['user_id'])];
//                $local_ids = [intval($_SESSION['user_id'])];
//            } else
//            {
//                $user_ids  = [intval($_SESSION['user_id']), $companion_id];
//                $local_ids = [$companion_id, intval($_SESSION['user_id'])];
//            }
//
//            if ($this->boundChat->getType() === 'dialog' && $this->boundChat->getCompanion()->getType() === 'bot')
//            {
//                if (intval($_SESSION['user_id']) < 0)
//                    $event['bot_peer_id'] = intval($_SESSION['user_id']);
//                if ($companion_id < 0)
//                    $event['bot_peer_id'] = $companion_id;
//            }
//        } else
//        {
//            $member_ids = $this->boundChat->getMembers();
//
//            foreach ($member_ids as $index => $user_info) {
//                $user_ids[]  = $user_info->user_id;
//                $local_ids[] = $user_info->local_id;
//            }
//        }
//
//        $this->boundChat->sendEvent($user_ids, $local_ids, $event, $this->getOwnerId());

        return 1;
    }

    public function getOwnerId (): int
    {
        return $this->owner_id;
    }

    public function getText (): string
    {
        return $this->text;
    }

    public function getTime (): int
    {
        return $this->time;
    }

    public function toArray (): array
    {
        $result = [
            'id'          => $this->getId(),
            'time'        => $this->getTime(),
            'from_id'     => $this->getOwnerId(),
            'fwd'         => [],
            'attachments' => []
        ];

        if (!$this->isServiceMessage())
        {
            $result['text']        = $this->getText();
            $result['fwd']         = array_map(function ($fwd)        { return $fwd->toArray(); },        $this->getForwarded());
            $result['attachments'] = array_map(function ($attachment) { return $attachment->toArray(); }, $this->getAttachments());
            $result['type']        = 'message';

            if ($this->isEdited())
                $result['is_edited'] = true;
        } else
        {
            $result['type']  = 'service_message';
            $result['event'] = [
                'action' => $this->event,
            ];

            if ($this->eventInfo)
            {
                if ($this->eventInfo->newTitle)
                    $result['event']['new_title'] = $this->eventInfo->newTitle;
                if ($this->eventInfo->newSrc)
                    $result['event']['new_photo_url'] = $this->eventInfo->newSrc;
                if ($this->eventInfo->actionerId)
                    $result['event']['to_id'] = $this->eventInfo->actionerId;
            }
        }

        $peer_id     = $this->boundChat->getLocalPeerId();
        $is_bot_chat = $peer_id < 0 && $this->boundChat->getUID() > 0;

        if ($is_bot_chat)
            $result['bot_peer_id'] = $peer_id;
        else
            $result['peer_id'] = $peer_id;

        return $result;
    }
}

?>
