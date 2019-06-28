<?php


namespace app\app\controller;

use app\model\Tags as Tag;

class Index extends Base
{
    public function tags(){
        $tags = cache('tags');
        if (!$tags){
            $tags = Tag::all();
            cache('tags',$tags,null,'redis');
        }

        $result = [
            'success' => 1,
            'tags' => $tags
        ];
        return json($result);
    }
}