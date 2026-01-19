<?php

// 数据库配置
$db_host = 'localhost';
$db_name = '数据库名';
$db_user = '数据库密码';
$db_pass = '数据库用户名';

// 管理员配置 多个用逗号分隔
$admin_ids = '777000,123456,789012'; 
$admin_ids_array = array_map('trim', explode(',', $admin_ids));

// bottoken
$botToken = "机器人Token";
// bot用户名
$bot_username = "机器人用户名";

// 暂存频道
$channel_id = -1003610000355; 
$channel_username = "username"; 

// 审核通过后转发的频道
$approved_channel_id = -1003660001159; 
$approved_channel_username = "username"; 

// 审核拒绝后转发的频道（设为'none'则不转发）
$rejected_channel_id = '-1003687000097'; 
$rejected_channel_username = 'username'; 

// 函数
function getPdo() {
    global $db_host, $db_name, $db_user, $db_pass;
    try {
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        error_log("DB Error: " . $e->getMessage());
        return null;
    }
}

// 检查用户是否是管理员
function isAdmin($userId) {
    global $admin_ids_array;
    return in_array($userId, $admin_ids_array);
}

// 发送消息给所有管理员
function sendMessageToAllAdmins($text, $parse_mode = 'HTML', $reply_markup = null) {
    global $admin_ids_array;
    $results = [];
    
    foreach ($admin_ids_array as $admin_id) {
        $data = [
            'chat_id' => $admin_id,
            'text' => $text,
            'parse_mode' => $parse_mode
        ];
        
        if ($reply_markup) {
            $data['reply_markup'] = json_encode($reply_markup);
        }
        
        $results[$admin_id] = apiRequest('sendMessage', $data);
    }
    
    return $results;
}

function apiRequest($method, $data) {
    global $botToken;
    $url = "https://api.telegram.org/bot$botToken/$method";
    
    if (isset($data['reply_markup']) && is_array($data['reply_markup'])) {
        $data['reply_markup'] = json_encode($data['reply_markup']);
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

// 删除消息函数
function deleteMessages($chat_id, $message_ids) {
    global $botToken;
    
    $deleted_count = 0;
    $message_id_array = is_array($message_ids) ? $message_ids : explode(',', $message_ids);
    
    foreach ($message_id_array as $msg_id) {
        $url = "https://api.telegram.org/bot$botToken/deleteMessage";
        $data = [
            'chat_id' => $chat_id,
            'message_id' => trim($msg_id)
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($ch);
        curl_close($ch);
        
        $result_json = json_decode($result, true);
        if ($result_json && $result_json['ok']) {
            $deleted_count++;
        }
    }
    
    return $deleted_count;
}

// send video
function fucksbZhaPianFan($media, $caption = "", $parse_mode = "HTML") {  
    global $botToken, $channel_id;
    
    $url = "https://api.telegram.org/bot$botToken/sendMediaGroup";
    
    $data = [
        'chat_id' => $channel_id,
        'media' => json_encode($media),
    ];
    
    if (!empty($caption)) {
        $data['caption'] = $caption;
        $data['parse_mode'] = $parse_mode;
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $result = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($result, true);
}

function forwardMessagesToChannel($msgIds, $targetChannelId, $caption = "", $parse_mode = "HTML") {
    global $botToken, $channel_id;
    
    if ($targetChannelId === 'none' || empty($msgIds)) {
        return ['ok' => false];
    }
    
    $msgIdArray = explode(',', $msgIds);

    if (count($msgIdArray) === 1) {
        $url = "https://api.telegram.org/bot$botToken/copyMessage";
        $data = [
            'chat_id' => $targetChannelId,
            'from_chat_id' => $channel_id,
            'message_id' => $msgIdArray[0]
        ];
    } 
    else {
        $url = "https://api.telegram.org/bot$botToken/copyMessages";
        $data = [
            'chat_id' => $targetChannelId,
            'from_chat_id' => $channel_id,
            'message_ids' => json_encode($msgIdArray)
        ];
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $result = curl_exec($ch);
    curl_close($ch);
    
    $res = json_decode($result, true);
    
    if ($res['ok']) {
        if (isset($res['result']['message_id'])) {
            $newIds = [$res['result']['message_id']];
        } else {
            $newIds = array_column($res['result'], 'message_id');
        }
        return ['ok' => true, 'result' => $newIds];
    }
    return ['ok' => false];
}

function generateChannelLink($messageId, $channelType = 'approved') {
    global $channel_username, $approved_channel_username, $rejected_channel_username;
    
    if ($channelType === 'approved' && $approved_channel_username !== 'none') {
        $username = $approved_channel_username;
    } elseif ($channelType === 'rejected' && $rejected_channel_username !== 'none') {
        $username = $rejected_channel_username;
    } else {
        $username = $channel_username;
    }
    
    $username = ltrim($username, '@');
    
    return "https://t.me/{$username}/{$messageId}";
}

//clean data
function clearUserTempData($pdo, $userId, $targetId, $mediaGroupId) {
    $pdo->prepare("DELETE FROM fanzha_temp_media WHERE user_id = ? AND target_id = ? AND media_group_id = ?")
        ->execute([$userId, $targetId, $mediaGroupId]);
}

// putin
$content = file_get_contents("php://input");
$update = json_decode($content, true);

// debug log
if (isset($update['callback_query'])) {
    error_log("收到回调查询: " . json_encode($update['callback_query']));
}

if (!$update) exit;

$message = $update["message"] ?? null;
$callback_query = $update["callback_query"] ?? null;
$my_chat_member = $update["my_chat_member"] ?? null; // get change information
$pdo = getPdo();

if (!$pdo) {
    error_log("数据库连接失败");
    exit;
}

if ($my_chat_member) {
    $chatId = $my_chat_member['chat']['id'];
    $newStatus = $my_chat_member['new_chat_member']['status'];
    $oldStatus = $my_chat_member['old_chat_member']['status'];

    // insetadmin
    if ($newStatus === 'administrator' && $oldStatus !== 'administrator') {
        $congratText = "<b>🎉 恭喜！权限升级成功！</b>\n\n✅ 我现在已获得管理员权限，可以正常执行自动扫描并拦截那些傻逼诈骗犯了。\n\n🛡️Made by Sakura";
        
        apiRequest('sendMessage', [
            'chat_id' => $chatId,
            'text' => $congratText,
            'parse_mode' => 'HTML' 
        ]);
    }
}

// menu
$mainKeyboard = [
    'inline_keyboard' => [
        [['text' => '📢 提交反诈', 'callback_data' => 'submit'], ['text' => '🔍 查询反诈', 'callback_data' => 'query']],
        [['text' => '👤 我的信息', 'callback_data' => 'me'], ['text' => 'ℹ️ 关于我们', 'callback_data' => 'about']],
        [['text' => '➕ 将我添加到群组', 'url' => "https://t.me/{$bot_username}?startgroup=true"]]
    ]
];

// 处理消息
if ($message) {
    $chatId = $message["chat"]["id"];
    $text = $message["text"] ?? "";
    $mediaGroupId = $message["media_group_id"] ?? null;
    $caption = $message["caption"] ?? "";
    
    // getputin
    $stmt = $pdo->prepare("SELECT step FROM fanzhauser WHERE user_id = :uid");
    $stmt->execute([':uid' => $chatId]);
    $userStep = $stmt->fetchColumn();

    // laru member
    if (isset($message['new_chat_members'])) {
        foreach ($message['new_chat_members'] as $newMember) {
            if ($newMember['username'] === $bot_username) {
                $welcomeText = "<b>👋 感谢将我拉入本群！</b>\n\n🛡️ 为了能实时识别并拦截诈骗犯，请将我 <b>[设置为管理员]</b> 并赋予 <b>[删除消息]</b> 权限。\n\n这样我可以更快捷地守护群友的财产安全！";
                
                apiRequest('sendMessage', [
                    'chat_id' => $chatId,
                    'text' => $welcomeText,
                    'parse_mode' => 'HTML' 
                ]);
            }
        }
    }

    // balck
    if ($message && isset($message['chat']) && ($message['chat']['type'] === 'group' || $message['chat']['type'] === 'supergroup')) {
        $fromId = $message['from']['id'];
        $firstName = htmlspecialchars($message['from']['first_name']);
        $msgId = $message['message_id'];

        $checkStmt = $pdo->prepare("SELECT id FROM fanzhasbzhapianfan WHERE target_id = ?");
        $checkStmt->execute([$fromId]);
        
        if ($checkStmt->fetch()) {
            // delete msg
            apiRequest('deleteMessage', [
                'chat_id' => $chatId,
                'message_id' => $msgId
            ]);

            // send warning
            $warningText = "⚠️ <b>群众里面有坏人！</b>\n\n👤 <b>用户：</b>{$firstName}\n🆔 <b>ID：</b><code>{$fromId}</code>\n\n该用户已被标记为诈骗。如有异议，请联系管理员申诉。";
            apiRequest('sendMessage', [
                'chat_id' => $chatId,
                'text' => $warningText,
                'parse_mode' => 'HTML'
            ]);

            //pkl
            apiRequest('restrictChatMember', [
                'chat_id' => $chatId,
                'user_id' => $fromId,
                'permissions' => json_encode([
                    'can_send_messages' => false,
                    'can_send_media_messages' => false,
                    'can_send_polls' => false,
                    'can_send_other_messages' => false,
                    'can_add_web_page_previews' => false,
                    'can_change_info' => false,
                    'can_invite_users' => false,
                    'can_pin_messages' => false
                ])
            ]);
            
            // tui
            exit;
        }
    }

    // common /ban /unban - 只有管理员可以使用
    if (strpos($text, '/ban') === 0 || strpos($text, '/unban') === 0) {
        if (isAdmin($chatId)) {
            $parts = explode(' ', $text);
            $action = $parts[0]; 
            $targetUid = $parts[1] ?? null;

            if ($targetUid && is_numeric($targetUid)) {
                $status = ($action === '/ban') ? 1 : 0;
                $statusText = ($action === '/ban') ? "封禁" : "解封";
                
                $stmt = $pdo->prepare("UPDATE fanzhauser SET is_banned = ? WHERE user_id = ?");
                $stmt->execute([$status, $targetUid]);
                
                if ($stmt->rowCount() > 0) {
                    $resMsg = "✅ 已成功{$statusText}用户：<code>$targetUid</code>";
                    
                    if ($status == 1) {
                        // msg
                        apiRequest('sendMessage', [
                            'chat_id' => $targetUid,
                            'text' => "⚠️ 您的投稿功能已被管理员封禁。"
                        ]);
                    } else {
                        // msg
                        apiRequest('sendMessage', [
                            'chat_id' => $targetUid,
                            'text' => "✅ 您的投稿功能已恢复，现在可以正常提交举报了。"
                        ]);
                    }
                    
                } else {
                    $resMsg = "⚠️ 操作完成，但未发现数据变动。";
                }
            } else {
                $resMsg = "❌ 格式错误。用法：<code>/ban 123456</code> 或 <code>/unban 123456</code>";
            }
            
            apiRequest('sendMessage', [
                'chat_id' => $chatId,
                'text' => $resMsg,
                'parse_mode' => 'HTML'
            ]);
            return; 
        }
    }

    // 删除记录命令 - 只有管理员可以使用
    if (strpos($text, '/shan') === 0) {
        if (isAdmin($chatId)) {
            $parts = explode(' ', $text);
            $targetAuditId = $parts[1] ?? null;

            if ($targetAuditId) {
                // 1. 先获取审核记录信息
                $stmt = $pdo->prepare("SELECT msg_ids, status FROM fanzhaunshenhe WHERE id = ?");
                $stmt->execute([$targetAuditId]);
                $auditRecord = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($auditRecord) {
                    // 2. 获取对应的黑名单记录（如果有）
                    $stmt2 = $pdo->prepare("SELECT msg_ids FROM fanzhasbzhapianfan WHERE audit_id = ?");
                    $stmt2->execute([$targetAuditId]);
                    $blacklistRecord = $stmt2->fetch(PDO::FETCH_ASSOC);
                    
                    $deletedChannelMessages = 0;
                    
                    // 3. 删除暂存频道的消息
                    if (!empty($auditRecord['msg_ids'])) {
                        $deletedChannelMessages += deleteMessages($channel_id, $auditRecord['msg_ids']);
                    }
                    
                    // 4. 如果审核已通过，删除通过频道的消息
                    if ($auditRecord['status'] === 'approved' && $blacklistRecord && !empty($blacklistRecord['msg_ids']) && $approved_channel_id !== 'none') {
                        $deletedChannelMessages += deleteMessages($approved_channel_id, $blacklistRecord['msg_ids']);
                    }
                    
                    // 5. 删除数据库记录
                    $stmt1 = $pdo->prepare("DELETE FROM fanzhasbzhapianfan WHERE audit_id = ?");
                    $stmt1->execute([$targetAuditId]);
                    $deletedBlacklistCount = $stmt1->rowCount();
                    
                    // 更新审核状态为已删除
                    $stmt3 = $pdo->prepare("UPDATE fanzhaunshenhe SET status = 'deleted' WHERE id = ?");
                    $stmt3->execute([$targetAuditId]);
                    $updatedAuditCount = $stmt3->rowCount();
                    
                    if ($deletedBlacklistCount > 0 || $updatedAuditCount > 0) {
                        $resMsg = "✅ 已成功删除审核编号为 <code>$targetAuditId</code> 的记录。\n";
                        $resMsg .= "📊 删除统计：\n";
                        $resMsg .= "• 频道消息删除数: $deletedChannelMessages 条\n";
                        $resMsg .= "• 黑名单记录删除数: $deletedBlacklistCount 条\n";
                        $resMsg .= "• 审核记录更新数: $updatedAuditCount 条";
                    } else {
                        $resMsg = "⚠️ 未找到编号为 <code>$targetAuditId</code> 的记录，请检查输入是否正确。";
                    }
                } else {
                    $resMsg = "⚠️ 未找到编号为 <code>$targetAuditId</code> 的审核记录。";
                }
            } else {
                $resMsg = "❌ 格式错误。用法：<code>/shan 审核编号</code>\n例如：<code>/shan a1b2c3</code>";
            }
            
            apiRequest('sendMessage', [
                'chat_id' => $chatId,
                'text' => $resMsg,
                'parse_mode' => 'HTML'
            ]);
            return; 
        }
    }

    if ($text === "/start") {
        // 处理/start
        $pdo->prepare("INSERT INTO fanzhauser (user_id, username, first_name, step) VALUES (:uid, :uname, :fname, 'none') 
                       ON DUPLICATE KEY UPDATE step = 'none'")
            ->execute([
                ':uid' => $chatId, 
                ':uname' => $message['from']['username'] ?? '', 
                ':fname' => $message['from']['first_name'] ?? ''
            ]);
        
        apiRequest('sendMessage', [
            'chat_id' => $chatId,
            'text' => "👋 欢迎使用反诈查询机器人！\n\n您可以点击下方按钮查询可疑 ID，或提交新的诈骗举报。",
            'reply_markup' => $mainKeyboard
        ]);
    } 
    // waidi
    elseif ($userStep === 'wait_query_id') {
        $targetId = isset($message['forward_from']) ? $message['forward_from']['id'] : (is_numeric($text) ? $text : "");
        
        if ($targetId) {
            // check
            $stmt = $pdo->prepare("SELECT * FROM fanzhasbzhapianfan WHERE target_id = ? ORDER BY added_at DESC");
            $stmt->execute([$targetId]);
            $scamRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($scamRecords) {
                $reply = "⚠️ **查询结果：该用户已被标记！**\n\n";
                $reply .= "🆔 **目标 ID:** `{$targetId}`\n";
                $reply .= "📊 **记录数量:** " . count($scamRecords) . " 条\n\n";
                $reply .= "🔗 **公开证据链接:**\n";
                
                foreach ($scamRecords as $index => $record) {
                    $recordNumber = $index + 1;
                    $addedDate = date('Y-m-d', strtotime($record['added_at']));
                    
                    // 解析消息ID列表
                    $msgIds = !empty($record['msg_ids']) ? explode(',', $record['msg_ids']) : [];
                    
                    if (!empty($msgIds) && !empty($msgIds[0])) {
                        $firstMsgId = $msgIds[0];
                        $channelLink = generateChannelLink($firstMsgId);
                        
                        $reply .= "{$recordNumber}. [记录 #{$recordNumber} ({$addedDate})]({$channelLink})\n";
                    } else {
                        $reply .= "{$recordNumber}. 记录 #{$recordNumber} ({$addedDate}) - 链接缺失\n";
                    }
                }
                
                $reply .= "\n❗ **请终止一切交易，保护财产安全。**";
                $reply .= "\n_注：同一人可能多次行骗，请查看所有记录。_";
            } else {
                $reply = "✅ **查询结果：暂时安全**\n\n库中未发现 ID `{$targetId}` 的记录。\n\n_注：未录入不代表绝对安全，请自行甄别。_";
            }
            
            apiRequest('sendMessage', [
                'chat_id' => $chatId,
                'text' => $reply,
                'parse_mode' => 'Markdown',
                'reply_markup' => $mainKeyboard
            ]);
            $pdo->prepare("UPDATE fanzhauser SET step = 'none' WHERE user_id = :uid")->execute([':uid' => $chatId]);
        } else {
            apiRequest('sendMessage', ['chat_id' => $chatId, 'text' => "❌ 输入无效！请输入纯数字 ID 或直接转发对方的消息。"]);
        }
    }
    elseif ($userStep === 'wait_target_id') {
        $targetId = isset($message['forward_from']) ? $message['forward_from']['id'] : (is_numeric($text) ? $text : "");
        
        if ($targetId) {
            $pdo->prepare("DELETE FROM fanzha_temp_media WHERE user_id = ? AND target_id = ?")
                ->execute([$chatId, $targetId]);
            
            apiRequest('sendMessage', [
                'chat_id' => $chatId,
                'text' => "✅ 已识别目标ID: <code>$targetId</code>\n\n📤 现在请发送诈骗证据：• 同时发送图片+文字（将文字作为图片说明）\n\n发送完成后，请发送 <code>/done</code> 结束提交。",
                'parse_mode' => 'HTML'
            ]);
            $pdo->prepare("UPDATE fanzhauser SET step = 'wait_content_$targetId' WHERE user_id = :uid")->execute([':uid' => $chatId]);
        } else {
            apiRequest('sendMessage', ['chat_id' => $chatId, 'text' => "❌ 识别失败！请发送数字ID或转发消息。"]);
        }
    }
    elseif ($userStep && strpos($userStep, 'wait_content_') === 0) {
        $targetId = str_replace('wait_content_', '', $userStep);
        
        // /done
        if ($text === '/done') {
            $stmt = $pdo->prepare("SELECT media_group_id FROM fanzha_temp_media WHERE user_id = ? AND target_id = ? GROUP BY media_group_id");
            $stmt->execute([$chatId, $targetId]);
            $mediaGroups = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (empty($mediaGroups)) {
                apiRequest('sendMessage', [
                    'chat_id' => $chatId,
                    'text' => "⚠️ 您还没有发送任何证据。请发送图片或文字证据，然后使用 /done 提交。\n\n或者发送 /cancel 取消本次提交。",
                    'parse_mode' => 'HTML'
                ]);
                return;
            }
            
            $submissionResults = [];
            
            // 为每个媒体组创建独立的审核记录
            foreach ($mediaGroups as $mediaGroupId) {
                $auditId = bin2hex(random_bytes(6));
                
                // 获取该媒体组的所有文件
                $stmt = $pdo->prepare("SELECT file_id, file_type, caption FROM fanzha_temp_media WHERE user_id = ? AND target_id = ? AND media_group_id = ? ORDER BY id");
                $stmt->execute([$chatId, $targetId, $mediaGroupId]);
                $mediaItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $msgIds = [];
                $contentInChannel = '';
                $evidenceType = '';
                
                if (!empty($mediaItems) && !empty($mediaItems[0]['file_id'])) {
                    // 有
                    $evidenceType = '图片证据';
                    
                    // 组
                    if ($mediaItems[0]['file_type'] === 'photo' || $mediaItems[0]['file_type'] === null) {
                        $media = [];
                        $firstCaption = '';
                        
                        foreach ($mediaItems as $index => $item) {
                            if (!empty($item['file_id'])) {
                                $media[] = [
                                    'type' => 'photo',
                                    'media' => $item['file_id']
                                ];
                                
                                if ($index === 0 && !empty($item['caption'])) {
                                    $firstCaption = $item['caption'];
                                    $contentInChannel = $item['caption'];
                                    $media[0]['caption'] = $item['caption'];
                                }
                            }
                        }
                        
                        if (empty($contentInChannel) && !empty($firstCaption)) {
                            $contentInChannel = $firstCaption;
                        }
                        
                        // 发
                        if (!empty($media)) {
                            $result = fucksbZhaPianFan($media, $firstCaption);
                            
                            if ($result && $result['ok']) {
                                foreach ($result['result'] as $msg) {
                                    $msgIds[] = $msg['message_id'];
                                }
                            } else {
                                error_log("发送媒体组失败: " . json_encode($result));
                            }
                        }
                    }
                } else {
                    $stmt = $pdo->prepare("SELECT caption FROM fanzha_temp_media WHERE user_id = ? AND target_id = ? AND media_group_id = ?");
                    $stmt->execute([$chatId, $targetId, $mediaGroupId]);
                    $textData = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($textData && !empty($textData['caption'])) {
                        $evidenceType = '文字证据';
                        $contentInChannel = $textData['caption'];
                        
                        // 纯文字
                        $result = json_decode(apiRequest('sendMessage', [
                            'chat_id' => $channel_id,
                            'text' => $contentInChannel,
                            'parse_mode' => 'HTML' 
                        ]), true);
                        
                        if ($result && $result['ok']) {
                            $msgIds[] = $result['result']['message_id'];
                        } else {
                            error_log("发送文字消息失败: " . json_encode($result));
                        }
                    }
                }
                
                // save审核表
                $msgIdsStr = implode(',', $msgIds);
                try {
                    $pdo->prepare("INSERT INTO fanzhaunshenhe (id, submitter_id, target_id, media_group_id, msg_ids, status) VALUES (?, ?, ?, ?, ?, 'pending')")
                        ->execute([$auditId, $chatId, $targetId, $mediaGroupId, $msgIdsStr]);
                } catch (Exception $e) {
                    error_log("保存审核记录失败: " . $e->getMessage());
                    continue;
                }
                $channelLink = '';
                if (!empty($msgIds) && !empty($msgIds[0])) {
                    $tempUsername = ltrim($channel_username, '@');
                    $channelLink = "https://t.me/{$tempUsername}/{$msgIds[0]}";
                }

                $submissionResults[] = [
                    'auditId' => $auditId,
                    'evidenceType' => $evidenceType,
                    'msgIds' => $msgIds,
                    'channelLink' => $channelLink
                ];

                $adminKb = ['inline_keyboard' => [[
                    ['text' => '✅ 通过', 'callback_data' => "approve_$auditId"], 
                    ['text' => '❌ 拒绝', 'callback_data' => "reject_$auditId"]
                ]]];

                $adminMessage = "📢 **新投稿**\n";
                $adminMessage .= "提交人: `$chatId`\n";
                $adminMessage .= "目标ID: `$targetId`\n";
                $adminMessage .= "审核编号: `$auditId`\n";
                $adminMessage .= "证据类型: $evidenceType\n";

                if (!empty($channelLink)) {
                    $adminMessage .= "暂存证据链接: $channelLink"; 
                }

                // 发送给所有管理员
                sendMessageToAllAdmins($adminMessage, 'Markdown', $adminKb);
                
                // clean
                clearUserTempData($pdo, $chatId, $targetId, $mediaGroupId);
            }
            
            // 通知用户
            $resultText = "✅ 证据提交完成！\n\n";
            foreach ($submissionResults as $result) {
                $resultText .= "• 审核编号: <code>{$result['auditId']}</code>\n";
            }
            $resultText .= "\n请等待管理员审核。";

            apiRequest('sendMessage', [
                'chat_id' => $chatId,
                'text' => $resultText,
                'parse_mode' => 'HTML'
            ]);
            
            $pdo->prepare("UPDATE fanzhauser SET step = 'none' WHERE user_id = :uid")->execute([':uid' => $chatId]);
            return;
        }
        
        // /cancel
        if ($text === '/cancel') {
            $pdo->prepare("DELETE FROM fanzha_temp_media WHERE user_id = ? AND target_id = ?")
                ->execute([$chatId, $targetId]);
            
            $pdo->prepare("UPDATE fanzhauser SET step = 'none' WHERE user_id = :uid")->execute([':uid' => $chatId]);
            
            apiRequest('sendMessage', [
                'chat_id' => $chatId,
                'text' => "❌ 已取消本次提交。",
                'reply_markup' => $mainKeyboard
            ]);
            return;
        }
        
        if (isset($message['photo']) && is_array($message['photo'])) {
            $photo = end($message['photo']);
            $fileId = $photo['file_id'];
            
            if (!$mediaGroupId) {
                $mediaGroupId = 'single_' . time() . '_' . rand(1000, 9999);
            }
            
            // save临时表
            try {
                $pdo->prepare("INSERT INTO fanzha_temp_media (user_id, target_id, media_group_id, file_id, file_type, caption) VALUES (?, ?, ?, ?, 'photo', ?)")
                    ->execute([$chatId, $targetId, $mediaGroupId, $fileId, $caption]);
                
                apiRequest('sendMessage', [
                    'chat_id' => $chatId,
                    'text' => "📸 已收到图片" . (!empty($caption) ? "（含说明）" : "") . "。\n\n您可以继续发送更多图片，或者发送文字说明。\n完成后请发送 <code>/done</code> 提交，或发送 <code>/cancel</code> 取消。",
                    'parse_mode' => 'HTML'
                ]);
            } catch (Exception $e) {
                error_log("保存图片错误: " . $e->getMessage());
                apiRequest('sendMessage', [
                    'chat_id' => $chatId,
                    'text' => "❌ 保存图片时出错，请稍后重试。"
                ]);
            }
        }
        elseif (!empty($text) && !isset($message['photo'])) {
            $textGroupId = 'text_' . time() . '_' . rand(1000, 9999);
            
            try {
                $pdo->prepare("INSERT INTO fanzha_temp_media (user_id, target_id, media_group_id, file_id, file_type, caption) VALUES (?, ?, ?, NULL, 'text', ?)")
                    ->execute([$chatId, $targetId, $textGroupId, $text]);
                
                apiRequest('sendMessage', [
                    'chat_id' => $chatId,
                    'text' => "📝 已收到文字说明。\n\n您可以继续发送图片或其他文字。\n完成后请发送 <code>/done</code> 提交，或发送 <code>/cancel</code> 取消。",
                    'parse_mode' => 'HTML'
                ]);
            } catch (Exception $e) {
                error_log("保存文字错误: " . $e->getMessage());
                apiRequest('sendMessage', [
                    'chat_id' => $chatId,
                    'text' => "❌ 保存文字时出错，请稍后重试。"
                ]);
            }
        }
        // 处理其他类型
        elseif (isset($message['document']) || isset($message['video'])) {
            apiRequest('sendMessage', [
                'chat_id' => $chatId,
                'text' => "⚠️ 目前仅支持图片和文字证据。请发送图片或文字说明。"
            ]);
        }
    }
}

// callback
if ($callback_query) {
    $data = $callback_query["data"];
    $cbChatId = $callback_query["message"]["chat"]["id"];
    $cbMsgId = $callback_query["message"]["message_id"];
    $cbId = $callback_query["id"];
    $cbFromId = $callback_query["from"]["id"];

    // 查询
    if ($data === "query") {
        $pdo->prepare("UPDATE fanzhauser SET step = 'wait_query_id' WHERE user_id = :uid")->execute([':uid' => $cbChatId]);
        apiRequest('editMessageText', [
            'chat_id' => $cbChatId,
            'message_id' => $cbMsgId,
            'text' => "🔍 请输入要查询的 TGID，或直接转发消息给我进行识别：",
            'parse_mode' => 'Markdown',
            'reply_markup' => ['inline_keyboard' => [[['text' => '⬅️ 返回', 'callback_data' => 'back_main']]]]
        ]);
    }
    // infir
    elseif ($data === "me") {
        $stmt = $pdo->prepare("SELECT created_at FROM fanzhauser WHERE user_id = ?");
        $stmt->execute([$cbChatId]);
        $regTime = $stmt->fetchColumn() ?: "未知";

        // 查询自己是否有被标记的记录
        $stmtScam = $pdo->prepare("SELECT * FROM fanzhasbzhapianfan WHERE target_id = ?");
        $stmtScam->execute([$cbChatId]);
        $scamRecords = $stmtScam->fetchAll(PDO::FETCH_ASSOC);
        
        $isScammer = count($scamRecords) > 0;

        $meText = "👤 **您的个人档案**\n\n";
        $meText .= "🔹 **用户 ID:** `{$cbChatId}`\n";
        $meText .= "📅 **注册时间:** `{$regTime}`\n";
       $stmtBan = $pdo->prepare("SELECT is_banned FROM fanzhauser WHERE user_id = ?");
       $stmtBan->execute([$cbChatId]);
       $isBanned = $stmtBan->fetchColumn();

       $meText .= "📝 **投稿权限:** " . ($isBanned == 1 ? "🚫 已封禁" : "✅ 正常") . "\n";
        if ($isScammer) {
            $meText .= "🛡️ **账号状态:** ⚠️ **异常（已被标记）**\n\n";
            $meText .= "⚠️ **违规记录详情：**\n";
            foreach ($scamRecords as $index => $record) {
                $num = $index + 1;
                $date = date('Y-m-d', strtotime($record['added_at']));
                $msgIds = explode(',', $record['msg_ids']);
                $link = generateChannelLink($msgIds[0], 'approved');
                $meText .= "{$num}. [证据记录 ({$date})]({$link})\n";
            }
            $meText .= "\n_如果您认为这是误报，请点击下方按钮进行申诉。_";
        } else {
            $meText .= "🛡️ **账号状态:** ✅ 正常\n\n";
            $meText .= "_提示：请继续保持良好的社交行为，共同维护社区环境。_";
        }

        // 构建按钮
        $buttons = [];
        if ($isScammer) {
            $buttons[] = [['text' => '⚖️ 提交申诉申请', 'callback_data' => 'appeal_request']];
        }
        $buttons[] = [['text' => '⬅️ 返回', 'callback_data' => 'back_main']];

        apiRequest('editMessageText', [
            'chat_id' => $cbChatId,
            'message_id' => $cbMsgId,
            'text' => $meText,
            'parse_mode' => 'Markdown',
            'disable_web_page_preview' => true,
            'reply_markup' => ['inline_keyboard' => $buttons]
        ]);
    }
    elseif ($data === "submit") {
        $stmt = $pdo->prepare("SELECT is_banned FROM fanzhauser WHERE user_id = ?");
        $stmt->execute([$cbChatId]);
        $isBanned = $stmt->fetchColumn();

        if ($isBanned == 1) {
            apiRequest('answerCallbackQuery', [
                'callback_query_id' => $cbId,
                'text' => '❌ 您的投稿功能已被限制，无法提交举报。',
                'show_alert' => true
            ]);
            return;
        }
        apiRequest('editMessageText', [
            'chat_id' => $cbChatId,
            'message_id' => $cbMsgId,
            'text' => "⚠️ 提交须知\n\n请确保证据真实有效。恶意举报、虚假投稿将会导致您的账号被系统拉黑。\n\n提交流程：\n1. 输入目标用户ID\n2. 发送证据（图片/文字）\n3. 发送 /done 完成提交\n4. 发送 /cancel 可取消提交\n\n您是否确认继续？",
            'reply_markup' => ['inline_keyboard' => [
                [['text' => '✅ 我确认并同意', 'callback_data' => 'confirm_submit']], 
                [['text' => '⬅️ 返回', 'callback_data' => 'back_main']]
            ]]
        ]);
    }
    elseif ($data === "confirm_submit") {
        $pdo->prepare("UPDATE fanzhauser SET step = 'wait_target_id' WHERE user_id = :uid")->execute([':uid' => $cbChatId]);
        apiRequest('editMessageText', [
            'chat_id' => $cbChatId,
            'message_id' => $cbMsgId,
            'text' => "请输入举报对象的 TGID，或直接转发对方的消息给我。",
            'reply_markup' => ['inline_keyboard' => [[['text' => '取消', 'callback_data' => 'back_main']]]]
        ]);
    }
    //管理审核 - pass
    elseif (strpos($data, 'approve_') === 0) {
        $aId = str_replace('approve_', '', $data);
        
        apiRequest('answerCallbackQuery', [
            'callback_query_id' => $cbId,
            'text' => '正在处理通过请求...',
            'show_alert' => false
        ]);
        
        try {
            $stmt = $pdo->prepare("SELECT * FROM fanzhaunshenhe WHERE id = ? AND status = 'pending'");
            $stmt->execute([$aId]);
            $audit = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($audit) {
                $originalChannelLink = '';
                if (!empty($audit['msg_ids'])) {
                    $msgIds = explode(',', $audit['msg_ids']);
                    if (!empty($msgIds[0])) {
                        $originalChannelLink = generateChannelLink($msgIds[0], 'original');
                    }
                }
                
                if ($approved_channel_id !== 'none' && !empty($audit['msg_ids'])) {
                    // 转发消息
                    $forwardResult = forwardMessagesToChannel($audit['msg_ids'], $approved_channel_id);
                    
                    if ($forwardResult['ok'] && !empty($forwardResult['result'])) {
                        // 获取转发后的消息ID
                        $newMsgIds = implode(',', $forwardResult['result']);

                        $insertStmt = $pdo->prepare("INSERT INTO fanzhasbzhapianfan (target_id, msg_ids, audit_id) VALUES (?, ?, ?)");
                        $insertResult = $insertStmt->execute([$audit['target_id'], $newMsgIds, $aId]);
                        
                        if ($insertResult) {
                            // 更新审核状态
                            $updateStmt = $pdo->prepare("UPDATE fanzhaunshenhe SET status = 'approved' WHERE id = ?");
                            $updateResult = $updateStmt->execute([$aId]);
                            
                            if ($updateResult) {
                                // 准备给管理员的消息
                                $approveText = "<b>✅ 审核已通过并入库</b>\n\n";
                                $approveText .= "审核编号: <code>$aId</code>\n";
                                $approveText .= "目标ID: <code>{$audit['target_id']}</code>\n";
                                $approveText .= "提交人ID: <code>{$audit['submitter_id']}</code>\n";
                                
                                // 生成通过频道的链接
                                $approvedChannelLink = '';
                                if (!empty($approved_channel_username) && $approved_channel_username !== 'none' && !empty($newMsgIds)) {
                                    $approvedUsername = ltrim($approved_channel_username, '@');
                                    $firstNewMsgId = explode(',', $newMsgIds)[0];
                                    $approvedChannelLink = "https://t.me/{$approvedUsername}/{$firstNewMsgId}";
                                    $approveText .= "公开证据链接: <a href=\"{$approvedChannelLink}\">{$approvedChannelLink}</a>\n";
                                }
                                
                                if (!empty($originalChannelLink)) {
                                    $approveText .= "原始证据链接: <a href=\"{$originalChannelLink}\">{$originalChannelLink}</a>\n";
                                }
                                
                                $approveText .= "\n入库时间: " . date('Y-m-d H:i:s');
                                
                                // 编辑管理员消息
                                apiRequest('editMessageText', [
                                    'chat_id' => $cbChatId, 
                                    'message_id' => $cbMsgId, 
                                    'text' => $approveText,
                                    'parse_mode' => 'HTML',
                                    'disable_web_page_preview' => true
                                ]);
                                
                                // 通知提交人
                                $userMessage = "🎉 <b>您的举报已通过审核！</b>\n\n";
                                $userMessage .= "审核编号: <code>$aId</code>\n";
                                $userMessage .= "目标ID: <code>{$audit['target_id']}</code>\n\n";
                                $userMessage .= "感谢您为反诈社区做出的贡献！\n\n";
                                
                                if (!empty($approvedChannelLink)) {
                                    $userMessage .= "公开证据链接:\n<code>{$approvedChannelLink}</code>";
                                }
                                
                                apiRequest('sendMessage', [
                                    'chat_id' => $audit['submitter_id'], 
                                    'text' => $userMessage,
                                    'parse_mode' => 'HTML',
                                    'disable_web_page_preview' => true
                                ]);
                                
                                error_log("审核通过成功: $aId, 目标ID: {$audit['target_id']}, 转发到频道: $approved_channel_id");
                            } else {
                                throw new Exception("更新审核状态失败");
                            }
                        } else {
                            throw new Exception("插入黑名单表失败");
                        }
                    } else {
                        throw new Exception("转发到审核通过频道失败");
                    }
                } else {
                    $msgIdsStr = $audit['msg_ids'];
                    $insertStmt = $pdo->prepare("INSERT INTO fanzhasbzhapianfan (target_id, msg_ids) VALUES (?, ?)");
                    $insertResult = $insertStmt->execute([$audit['target_id'], $msgIdsStr]);
                    
                    if ($insertResult) {
                        // 更新审核状态
                        $updateStmt = $pdo->prepare("UPDATE fanzhaunshenhe SET status = 'approved' WHERE id = ?");
                        $updateResult = $updateStmt->execute([$aId]);
                        
                        if ($updateResult) {
                            $approveText = "<b>✅ 审核已通过并入库</b>\n\n";
                            $approveText .= "审核编号: <code>$aId</code>\n";
                            $approveText .= "目标ID: <code>{$audit['target_id']}</code>\n";
                            $approveText .= "提交人ID: <code>{$audit['submitter_id']}</code>\n";
                            $approveText .= "媒体组ID: <code>{$audit['media_group_id']}</code>\n";
                            
                            if (!empty($msgIdsStr)) {
                                $approveText .= "频道消息ID: <code>{$msgIdsStr}</code>\n";
                            }
                            
                            if (!empty($originalChannelLink)) {
                                $approveText .= "证据链接: <a href=\"{$originalChannelLink}\">{$originalChannelLink}</a>";
                            }
                            
                            apiRequest('editMessageText', [
                                'chat_id' => $cbChatId, 
                                'message_id' => $cbMsgId, 
                                'text' => $approveText,
                                'parse_mode' => 'HTML',
                                'disable_web_page_preview' => true
                            ]);
                            
                            // 通知提交人
                            $userMessage = "🎉 <b>您的举报已通过审核！</b>\n\n";
                            $userMessage .= "审核编号: <code>$aId</code>\n";
                            $userMessage .= "目标ID: <code>{$audit['target_id']}</code>\n\n";
                            $userMessage .= "感谢您为反诈社区做出的贡献！\n\n";
                            
                            if (!empty($originalChannelLink)) {
                                $userMessage .= "证据链接:\n<code>{$originalChannelLink}</code>";
                            }
                            
                            apiRequest('sendMessage', [
                                'chat_id' => $audit['submitter_id'], 
                                'text' => $userMessage,
                                'parse_mode' => 'HTML',
                                'disable_web_page_preview' => true
                            ]);
                            
                            error_log("审核通过成功（无转发）: $aId, 目标ID: {$audit['target_id']}");
                        } else {
                            throw new Exception("更新审核状态失败");
                        }
                    } else {
                        throw new Exception("插入黑名单表失败");
                    }
                }
            } else {
                apiRequest('editMessageText', [
                    'chat_id' => $cbChatId, 
                    'message_id' => $cbMsgId, 
                    'text' => "❌ <b>未找到待审核的记录或记录已被处理。</b>\n\nID: <code>$aId</code>",
                    'parse_mode' => 'HTML'
                ]);
                error_log("未找到待审核记录: $aId");
            }
        } catch (Exception $e) {
            error_log("审核通过时出错: " . $e->getMessage());
            
            // 获取详细错误信息
            $errorDetails = "❌ <b>处理通过请求时出错</b>\n\n";
            $errorDetails .= "错误: " . htmlspecialchars($e->getMessage()) . "\n";
            $errorDetails .= "审核编号: <code>$aId</code>\n";
            $errorDetails .= "时间: " . date('Y-m-d H:i:s');
            
            apiRequest('editMessageText', [
                'chat_id' => $cbChatId, 
                'message_id' => $cbMsgId, 
                'text' => $errorDetails,
                'parse_mode' => 'HTML'
            ]);
            
            // 再次回应错误
            apiRequest('answerCallbackQuery', [
                'callback_query_id' => $cbId,
                'text' => '处理失败，请查看日志',
                'show_alert' => true
            ]);
        }
    }
    // 管理审核 - 拒绝
    elseif (strpos($data, 'reject_') === 0) {
        $aId = str_replace('reject_', '', $data);
        
        apiRequest('answerCallbackQuery', [
            'callback_query_id' => $cbId,
            'text' => '正在处理拒绝请求...',
            'show_alert' => false
        ]);
        
        try {
            // 获取审核记录信息，提交人ID
            $stmt = $pdo->prepare("SELECT * FROM fanzhaunshenhe WHERE id = ? AND status = 'pending'");
            $stmt->execute([$aId]);
            $audit = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($audit) {
                if ($rejected_channel_id !== 'none' && !empty($audit['msg_ids'])) {
                    forwardMessagesToChannel($audit['msg_ids'], $rejected_channel_id);
                }
                
                $updateStmt = $pdo->prepare("UPDATE fanzhaunshenhe SET status = 'rejected' WHERE id = ?");
                $result = $updateStmt->execute([$aId]);
                
                if ($result && $updateStmt->rowCount() > 0) {
                    apiRequest('editMessageText', [
                        'chat_id' => $cbChatId, 
                        'message_id' => $cbMsgId, 
                        'text' => "❌ 已拒绝投稿。ID: <code>$aId</code>",
                        'parse_mode' => 'HTML'
                    ]);
                    
                    // 通知用户投稿被拒绝
                    $userMessage = "❌ 您的举报 (审核编号: <code>$aId</code>) 已被管理员拒绝。\n\n原因：证据不足或不符合要求。";
                    apiRequest('sendMessage', [
                        'chat_id' => $audit['submitter_id'], 
                        'text' => $userMessage,
                        'parse_mode' => 'HTML'
                    ]);
                    
                    error_log("审核拒绝成功: $aId");
                } else {
                    apiRequest('editMessageText', [
                        'chat_id' => $cbChatId, 
                        'message_id' => $cbMsgId, 
                        'text' => "❌ 更新审核状态失败。ID: <code>$aId</code>",
                        'parse_mode' => 'HTML'
                    ]);
                    error_log("更新审核状态失败: $aId");
                }
            } else {
                apiRequest('editMessageText', [
                    'chat_id' => $cbChatId, 
                    'message_id' => $cbMsgId, 
                    'text' => "❌ 未找到待审核的记录或记录已被处理。ID: <code>$aId</code>",
                    'parse_mode' => 'HTML'
                ]);
                error_log("未找到待审核记录(拒绝): $aId");
            }
        } catch (Exception $e) {
            error_log("审核拒绝时出错: " . $e->getMessage());
            apiRequest('editMessageText', [
                'chat_id' => $cbChatId, 
                'message_id' => $cbMsgId, 
                'text' => "❌ 处理拒绝请求时出错: " . $e->getMessage()
            ]);
        }
    }
    // 其他
    elseif ($data === "appeal_request") {
        apiRequest('answerCallbackQuery', [
            'callback_query_id' => $cbId,
            'text' => '请准备好相关证据，联系管理员进行人工复核。',
            'show_alert' => true
        ]);
        
        $appealText = "⚖️ **申诉指南**\n\n若您认为标记有误，请通过以下方式联系管理员：\n\n1. 准备您的 **UID**: `{$cbChatId}`\n2. 准备可以证明您清白的证据截图。\n3. 点击下方按钮联系客服。\n\n*恶意申诉将导致永久封禁。*";
        
        apiRequest('editMessageText', [
            'chat_id' => $cbChatId,
            'message_id' => $cbMsgId,
            'text' => $appealText,
            'parse_mode' => 'Markdown',
            'reply_markup' => ['inline_keyboard' => [
                [['text' => '👨‍💻 联系管理员', 'url' => "tg://user?id={$admin_ids_array[0]}"]],
                [['text' => '⬅️ 返回', 'callback_data' => 'me']]
            ]]
        ]);
    }

    elseif ($data === "about") {
        apiRequest('editMessageText', [
            'chat_id' => $cbChatId,
            'message_id' => $cbMsgId,
            'text' => "🛡️ 关于反诈 Bot\n\n旨在通过社区力量标记 Telegram 上的诈骗账号。数据来源于用户提交并由人工审核。开源共建，保护环境。",
            'reply_markup' => ['inline_keyboard' => [[['text' => '返回', 'callback_data' => 'back_main']]]]
        ]);
    }
    elseif ($data === "back_main") {
        $pdo->prepare("UPDATE fanzhauser SET step = 'none' WHERE user_id = :uid")->execute([':uid' => $cbChatId]);
        apiRequest('editMessageText', [
            'chat_id' => $cbChatId,
            'message_id' => $cbMsgId,
            'text' => "👋 欢迎使用反诈查询机器人！\n\n您可以点击下方按钮查询可疑 ID，或提交新的诈骗举报。",
            'reply_markup' => $mainKeyboard
        ]);
    }

    // 总是200
    if (!isset($callback_answered)) {
        apiRequest('answerCallbackQuery', ['callback_query_id' => $cbId]);
    }
}
