<?php
// import facade

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

if (!function_exists('get_user_info')) {
    function get_user_info($user_id = '')
    {
        $user_data = DB::table('users')->where('id', $user_id)->first();
        return $user_data;
    }
}

if (!function_exists('get_user_images')) {
    function get_user_images($file_name_or_user_id = "", $optimized = "")
    {

        $optimized = $optimized . '/';
        if ($file_name_or_user_id == '') {
            $file_name_or_user_id = 'default.png';
        }
        if (is_numeric($file_name_or_user_id)) {
            $user_id = $file_name_or_user_id;
            $file_name = "";
        } else {
            $user_id = "";
            $file_name = $file_name_or_user_id;
        }

        if ($user_id > 0) {
            $user_id = $file_name_or_user_id;
            $file_name = DB::table('users')->where('id', $user_id)->value('photo');

            //this file comes from another online link as like amazon s3 server
            if (strpos($file_name, 'https://') !== false) {
                return $file_name;
            }

            if (File::exists('public/storage/userimage/' . $optimized . $file_name) && is_file('public/storage/userimage/' . $optimized . $file_name)) {
                return url('public/storage/userimage/' . $optimized . $file_name);
            } else {
                return url('public/storage/userimage/default.png');
            }
        } elseif (File::exists('public/storage/userimage/' . $optimized . $file_name) && is_file('public/storage/userimage/' . $optimized . $file_name)) {
            return url('public/storage/userimage/' . $optimized . $file_name);
        } elseif (strpos($file_name, 'https://') !== false) {
            //this file comes from another online link as like amazon s3 server
            return $file_name;
        } else {
            return url('public/storage/userimage/default.png');
        }
    }
}

if (!function_exists('get_cover_photos')) {
    function get_cover_photos($file_name_or_user_id = '', $optimized = "")
    {

        $optimized = $optimized . '/';
        if ($file_name_or_user_id == '') {
            $file_name_or_user_id = Auth()->user()->cover_photo;
        }
        if (is_numeric($file_name_or_user_id)) {
            $user_id = $file_name_or_user_id;
            $file_name = "";
        } else {
            $user_id = "";
            $file_name = $file_name_or_user_id;
        }

        if ($user_id > 0) {
            $user_id = $file_name_or_user_id;
            $file_name = DB::table('users')->where('id', $user_id)->value('cover_photo');

            //this file comes from another online link as like amazon s3 server
            if (strpos($file_name, 'https://') !== false) {
                return $file_name;
            }

            if (File::exists('public/storage/cover_photo/' . $optimized . $file_name) && is_file('public/storage/cover_photo/' . $optimized . $file_name)) {
                return url('public/storage/cover_photo/' . $optimized . $file_name);
            } else {
                return url('public/storage/cover_photo/default.jpg');
            }
        } elseif (File::exists('public/storage/cover_photo/' . $optimized . $file_name) && is_file('public/storage/cover_photo/' . $optimized . $file_name)) {
            return url('public/storage/cover_photo/' . $optimized . $file_name);
        } elseif (strpos($file_name, 'https://') !== false) {
            //this file comes from another online link as like amazon s3 server
            return $file_name;
        } else {
            return url('public/storage/cover_photo/default.jpg');
        }
    }
}

if (!function_exists('get_post_images')) {
    function get_post_images($file_name = '', $optimized = "")
    {
        //this file comes from another online link as like amazon s3 server
        if (strpos($file_name, 'https://') !== false) {
            return $file_name;
        }

        $optimized = $optimized . '/';
        if (File::exists('public/storage/post/images/' . $optimized . $file_name) && is_file('public/storage/post/images/' . $optimized . $file_name)) {
            return url('public/storage/post/images/' . $optimized . $file_name);
        } else {
            return url('public/storage/post/images/default.png');
        }
    }
}
if (!function_exists('get_post_videos')) {
    function get_post_videos($file_name = '', $optimized = "")
    {
        //this file comes from another online link as like amazon s3 server
        if (strpos($file_name, 'https://') !== false) {
            return $file_name;
        }

        if ($optimized != "") {
            $optimized = $optimized . '/';
        }
        if (File::exists('public/storage/post/videos/' . $optimized . $file_name)) {
            return url('public/storage/post/videos/' . $optimized . $file_name);
        } else {
            return url('public/storage/post/videos/default.png');
        }
    }
}
if (!function_exists('get_story_images')) {
    function get_story_images($file_name = '', $optimized = "")
    {
        //this file comes from another online link as like amazon s3 server
        if (strpos($file_name, 'https://') !== false) {
            return $file_name;
        }

        $optimized = $optimized . '/';
        if (File::exists('public/storage/story/images/' . $optimized . $file_name) && is_file('public/storage/story/images/' . $optimized . $file_name)) {
            return url('public/storage/story/images/' . $optimized . $file_name);
        } else {
            return url('public/storage/story/images/default.jpg');
        }
    }
}
if (!function_exists('get_story_videos')) {
    function get_story_videos($file_name = '', $optimized = "")
    {
        //this file comes from another online link as like amazon s3 server
        if (strpos($file_name, 'https://') !== false) {
            return $file_name;
        }

        if ($optimized != "") {
            $optimized = $optimized . '/';
        }
        if (File::exists('public/storage/story/videos/' . $optimized . $file_name)) {
            return url('public/storage/story/videos/' . $optimized . $file_name);
        } else {
            return url('public/storage/story/videos/default.jpg');
        }
    }
}
//get page logo
if (!function_exists('get_group_logos')) {
    function get_group_logos($file_name = "", $foldername = "")
    {
        //this file comes from another online link as like amazon s3 server
        if (strpos($file_name, 'https://') !== false) {
            return $file_name;
        }

        $foldername = $foldername . '/';

        // if (!empty($file_name) && !empty($foldername)) {
        if ($file_name != "" && $foldername != "") {
            return url('public/storage/groups/' . $foldername . $file_name);
        } else {
            return url('public/storage/groups/' . $foldername . 'default/default.jpg');
        }
        // }
        //  else {
        //     return url('public/storage/groups/' . $foldername . 'default/default.jpg');
        // }
    }

}

//get group cover photo
if (!function_exists('get_group_cover_photos')) {
    function get_group_cover_photos($file_name = "", $foldername = "")
    {
        //this file comes from another online link as like amazon s3 server
        if (strpos($file_name, 'https://') !== false) {
            return $file_name;
        }

        $foldername = $foldername . '/';

        if (!empty($file_name)) {
            // if (File::exists('public/storage/groups/' . $foldername . $file_name)) {
            return url('public/storage/groups/' . $foldername . $file_name);
            // } else {
            //     return url('public/storage/groups/' . $foldername . 'default/default.jpg');
            // }
        } else {
            return url('public/storage/groups/' . $foldername . 'default/default.jpg');
        }
    }

}
//get all assets photo
if (!function_exists('get_all_assets_photos')) {
    function get_all_assets_photos($file_name = "", $foldername = "",$main_foldername = "")
    {
        //this file comes from another online link as like amazon s3 server
        if (strpos($file_name, 'https://') !== false) {
            return $file_name;
        }

        $foldername = $foldername . '/';
        $main_foldername = $main_foldername . '/';

        if (!empty($file_name)) {
            // if (File::exists('public/storage/groups/' . $foldername . $file_name)) {
            return url('public/assets/frontend/'.$main_foldername . $foldername . $file_name);
            // } else {
            //     return url('public/storage/groups/' . $foldername . 'default/default.jpg');
            // }
        } else {
            return url('public/assets/frontend/'.$main_foldername  . $foldername . 'default/default.jpg');
        }
    }

}
//get assets photo
if (!function_exists('get_group_event_photos')) {
    function get_group_event_photos($file_name = "", $foldername = "",$main_foldername = "")
    {
        //this file comes from another online link as like amazon s3 server
        if (strpos($file_name, 'https://') !== false) {
            return $file_name;
        }

        $foldername = $foldername . '/';
        $main_foldername = $main_foldername . '/';

        if (!empty($file_name)) {
            // if (File::exists('public/storage/groups/' . $foldername . $file_name)) {
            return url('public/storage/'.$main_foldername . $foldername . $file_name);
            // } else {
            //     return url('public/storage/groups/' . $foldername . 'default/default.jpg');
            // }
        } else {
            return url('public/storage/'.$main_foldername  . $foldername . 'default/default.jpg');
        }
    }

}
//get one folders file
if (!function_exists('get_one_folder_files')) {
    function get_one_folder_files($file_name = "", $foldername = "")
    {
        //this file comes from another online link as like amazon s3 server
        if (strpos($file_name, 'https://') !== false) {
            return $file_name;
        }

        $foldername = $foldername . '/';

        if (!empty($file_name)) {
            // if (File::exists('public/storage/groups/' . $foldername . $file_name)) {
            return url('public/storage/'. $foldername . $file_name);
            // } else {
            //     return url('public/storage/groups/' . $foldername . 'default/default.jpg');
            // }
        } else {
            return url('public/storage/'  . $foldername . 'default/default.jpg');
        }
    }

}

// for group
if (!function_exists('members_by_group_id')) {
    function members_by_group_id($group_id = "")
    {
        // Assuming you have the appropriate model for the group members table
        // Replace 'GroupMember' with your actual model name
        $group_members = Group_member::where('group_id', $group_id)->get();

        $response = [];

        // Check if any members were found
        if ($group_members->count() > 0) {
            // Iterate through the group members
            foreach ($group_members as $member) {
                $user = DB::table('users')->where('id', $member->user_id)->first();

                // Parse friends list JSON string
                $friendsList = json_decode($user->friends, true);
                $countfriends = count($friendsList);

                $matchingFriendsCount = 0;
                foreach ($friendsList as $friendId) {
                    // Check if the friend ID matches the member's user ID
                    if ($friendId == $member->user_id) {
                        $matchingFriendsCount++;
                    }
                }

                // // Prepare an array to store friend details
                // $friends = [];
                // // Iterate through the friends list
                // foreach ($friendsList as $friendId => $friendCount) {
                //     // Fetch details of each friend
                //     $friend = DB::table('users')->where('id', $friendId)->first();
                //     if ($friend) {
                //         $friends[] = [
                //             'id' => $friend->id,
                //             'name' => $friend->name,
                //             'photo' => get_user_images($friend->id),
                //             // Add other friend details as needed
                //         ];
                //     }
                // }

                // Add each member's ID and friend details to the response array
                $response[] = [
                    'id' => $member->id,
                    'user_id' => $member->user_id,
                    'group_id' => $member->group_id,
                    'name' => $user->name,
                    'photo' => get_user_images($user->id),
                    'countfriends' => $countfriends,
                    'matching_friends_count' => $matchingFriendsCount,
                    // 'is_accepted' => $member->is_accepted,
                    // 'role' => $member->role,
                    // 'created_at' => $member->created_at,
                    // 'updated_at' => $member->updated_at,
                ];
            }
        }

        return $response;
    }
}
