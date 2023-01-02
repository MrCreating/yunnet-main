<?php

/**
 * ADMIN PANEL!!
*/

use unt\exceptions\EntityNotFoundException;
use unt\objects\Bot;
use unt\objects\Context;
use unt\objects\Photo;
use unt\objects\Project;
use unt\objects\Request;
use unt\parsers\AttachmentsParser;
use unt\platform\AccountManager;
use unt\platform\DataBaseManager;

$current_user_level = Context::get()->getCurrentUser()->getAccessLevel();

if (!($current_user_level < 1 || !Context::get()->allowToUseUnt()))
{
	if (isset(Request::get()->data['action']))
	{
        try {
            $action  = strtolower(strval(Request::get()->data['action']));
            $user_id = intval(Request::get()->data['user_id']) === 0 ? intval($_SESSION['user_id']) : intval(Request::get()->data['user_id']);

            $manager = AccountManager::create($user_id);
            $user    = $manager->getEntity();

            switch ($action)
            {
                case 'show_project_files':
                    if ($current_user_level < 4) die(json_encode(array('error' => 1)));

                    $filePath = PROJECT_ROOT . Request::get()->data['file_path'];

                    $response = Project::getFiles($filePath);

                    die(json_encode(array('response' => $response)));
                    break;

                case 'toggle_ban_state':
                    if ($current_user_level >= 3  && $user->getAccessLevel() < $current_user_level)
                    {
                        if ($user->getAccessLevel() !== 4)
                        {
                            if ($manager->ban())
                            {
                                die(json_encode(array('state' => !intval($user->isBanned()))));
                            }
                        }
                    }
                    break;

                case 'toggle_verification_state':
                    if ($user->getAccessLevel() <= $current_user_level)
                    {
                        if ($manager->verify())
                            die(json_encode(array('state' => !intval($user->isVerified()))));
                    }
                    break;

                case 'toggle_online_show_state':
                    if ($current_user_level >= 4 && $user->getAccessLevel() <= $current_user_level)
                    {
                        if ($user->getType() !== Bot::ENTITY_TYPE)
                        {
                            if ($manager->toggleOnlineHidden())
                                die(json_encode(array('state' => !intval($user->getOnline()->isOnlineHidden))));
                        }
                    }
                    break;

                case 'edit_user':
                    if ($current_user_level >= 2 && $user->getAccessLevel() <= $current_user_level)
                    {
                        if ($user->getFirstName() !== Request::get()->data["first_name"] && isset(Request::get()->data['first_name']))
                        {
                            $changed = $user->edit()->setFirstName(Request::get()->data["first_name"]);
                            if ($changed !== false && $changed !== true)
                            {
                                switch ($changed)
                                {
                                    case -2:
                                    case -1:
                                        die(json_encode(array('error' => 1, 'message' => Context::get()->getLanguage()->bad_data_fn)));
                                        break;
                                    case -3:
                                        die(json_encode(array('error' => 1, 'message' => Context::get()->getLanguage()->need_all_data)));
                                        break;
                                }
                            }

                            die(json_encode(array('response'=>1)));
                        }
                        if ($user->getLastName() !== Request::get()->data["last_name"] && isset(Request::get()->data['last_name']))
                        {
                            $changed = $user->edit()->setLastName(Request::get()->data["last_name"]);
                            if ($changed !== false && $changed !== true)
                            {
                                switch ($changed)
                                {
                                    case -2:
                                    case -1:
                                        die(json_encode(array('error'=>1, 'message'=>Context::get()->getLanguage()->bad_data_ln)));
                                        break;
                                    case -3:
                                        die(json_encode(array('error'=>1, 'message'=>Context::get()->getLanguage()->need_all_data)));
                                        break;
                                }
                            }

                            die(json_encode(array('response'=>1)));
                        }

                        if ($user->getScreenName() !== Request::get()->data["screen_name"] && isset(Request::get()->data['screen_name']))
                        {
                            $result = $user->edit()->setScreenName(is_empty(Request::get()->data["screen_name"]) ? NULL : Request::get()->data["screen_name"]);
                            if ($result === 0)
                            {
                                die(json_encode(array('error'=>1, 'message'=>Context::get()->getLanguage()->in_f_3)));
                            }
                            if ($result === -1)
                            {
                                die(json_encode(array('error'=>1, 'message'=>Context::get()->getLanguage()->in_f_4)));
                            }

                            die(json_encode(array('response'=>1)));
                        }

                        if (isset(Request::get()->data['photo']))
                        {
                            $attachment_data = strval(Request::get()->data['photo']);
                            if (is_empty($attachment_data))
                            {
                                $result = $user->setPhoto()->apply();
                                if ($result) die(json_encode(array('response'=>1)));
                            } else
                            {
                                $attachment = (new AttachmentsParser())->getObject($attachment_data);
                                if ($attachment instanceof Photo) {
                                    $result = $user->setPhoto($attachment)->apply();

                                    if ($result) die(json_encode($result->toArray()));
                                }

                                die(json_encode(array('error' => 1)));
                            }
                        }

                        die(json_encode(array('error' => 1, 'message' => Context::get()->getLanguage()->in_f_2)));
                    }
                    break;

                case 'get_project_settings':
                    if ($current_user_level >= 4)
                    {
                        $closed_project = Project::isClosed();
                        $closed_register = Project::isRegisterClosed();

                        die(json_encode(array('closed_project' => intval($closed_project), 'closed_register' => intval($closed_register))));
                    }
                    break;

                case 'toggle_project_close':
                    if ($current_user_level >= 4)
                    {
                        die(json_encode(array('response' => intval(Project::toggleClose()))));
                    }
                    break;

                case 'toggle_register_close':
                    if ($current_user_level >= 4)
                    {
                        die(json_encode(array('response' => intval(Project::toggleRegistrationClose()))));
                    }
                    break;

                case 'delete_user':
                    if ($current_user_level >= 3 && $user->getAccessLevel() <= $current_user_level)
                    {
                        die(json_encode(array('success' => (int) $manager->deleteEntity())));
                    }
                    break;

                case 'run_sql_query':
                    if ($current_user_level >= 3)
                    {
                        $sql_query = Request::get()->data['sql_query'];

                        DataBaseManager::getConnection()->getClient()->prepare('START TRANSACTION')->execute();

                        $res = DataBaseManager::getConnection()->getClient()->prepare($sql_query);
                        $result = '';

                        if ($res->execute()) {
                            $result = json_encode(array(
                                'response' => $res->fetchAll(\PDO::FETCH_ASSOC)
                            ));
                        } else {
                            $error = DataBaseManager::getConnection()->getClient()->errorInfo();

                            $result = json_encode(array(
                                'fail' => $error
                            ));
                        }

                        DataBaseManager::getConnection()->getClient()->prepare('ROLLBACK')->execute();
                        die($result);
                    }
                    break;

                default:
                    break;
            }
        } catch (EntityNotFoundException $e)
        {
            die(json_encode(array('error' => 1)));
        }
	}
}

die(json_encode(array('error' => 1)));
?>