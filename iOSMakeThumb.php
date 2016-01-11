<?php
class iOSMakeThumb
{
    // 3x图片转2x
    private $src_size  = 3;
    private $to_size   = 2;
    private $ext_name  = 'png';


    public function make($path)
    {
        echo $path."\n";
        $directory = new RecursiveDirectoryIterator($path);
        $recursive = new RecursiveIteratorIterator($directory,RecursiveIteratorIterator::SELF_FIRST);

        foreach($recursive as $file) {
            // @3x
            $orig_flag = '@'.$this->src_size.'x';
            if ($file->isFile() && strtolower(substr($file->getFilename(), -3)) == strtolower($this->ext_name) && stripos($file->getFilename(), $orig_flag)) {
                echo $file->getPathname();
                $new_file_name = $this->get_new_name($file->getFilename());
                $save_file_name = $file->getPath().'/'.$new_file_name;

                $this->make_image($file->getPathname(), $file->getPath().'/'.$new_file_name);
                if (file_exists($save_file_name)) {
                    echo $save_file_name;
                    echo "\n";
                    $this->parse_json($file->getPath(), $new_file_name);
                }
            }


        }
    }

    /**
     * 处理完图片，把2x图片写回JSON
     * @param unknown_type $path Content.json 路径
     * @param unknown_type $new_file_name 2x 文件名
     */
    private function parse_json($path, $new_file_name)
    {
        $json_file =$path.'/Contents.json';
        if (file_exists($json_file)) {

            $json_data = file_get_contents($json_file);
            $data = json_decode($json_data, true);

            if (isset($data['images'])) {
                $data['images'][$this->to_size - 1]['filename'] = $new_file_name;
                file_put_contents($json_file, json_encode($data));
            }
        }
    }

    /**
     * 生成2x图片
     * @param unknown_type $path_file  3x 原图
     * @param unknown_type $save_file_path 2x 保存的位置
     */
    private function make_image($path_file, $save_file_path)
    {
        $size_info = getimagesize($path_file);
        if ($size_info) {

            $new_size_info = $this->get_new_size($size_info);
            $orig_image    = imagecreatefrompng($path_file);

            imagesavealpha($orig_image,true);
            $new_image = imagecreatetruecolor($new_size_info['width'], $new_size_info['height']);

            // 不合并颜色,包括透明色;
            imagealphablending($new_image,false);

            // 不要丢了图像的透明色;
            imagesavealpha($new_image,true);

            if(imagecopyresampled($new_image,$orig_image,0,0,0,0,$new_size_info['width'],$new_size_info['height'],$size_info[0],$size_info[1])){
                imagepng($new_image,$save_file_path);
            }

            imagedestroy($new_image);
            imagedestroy($orig_image);
        }
    }

    /**
     * 计算3x转2x的比例
     * @param unknown_type $size_info
     * @return multitype:number
     */
    private function get_new_size($size_info)
    {
       $orig_width  = ceil($size_info[0] / $this->src_size);
       $orig_height = ceil($size_info[1] / $this->src_size);

       return array(
                   'width'=>$orig_width * $this->to_size,
                   'height'=>$orig_height * $this->to_size
              );
    }

    /**
     * 获取新的文件名
     * @param unknown_type $file_name
     * @return mixed
     */
    private function get_new_name($file_name)
    {
        $src_flag = '@'.$this->src_size.'x';
        $to_flag = '@'.$this->to_size.'x';
        return str_replace($src_flag, $to_flag, $file_name);
    }
}

if (!isset($argv[1])) {
    echo "Run：/usr/bin/php make_ios.php /root/images\n";
    exit(3);
}

set_time_limit(0);
$path = $argv[1];
$thumb = new iOSMakeThumb();
$thumb->make($path);