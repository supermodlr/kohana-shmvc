<?php

class Kohana_SHMVC_View extends Kohana_View {


    /**
     * Returns a new View object. If you do not define the "file" parameter,
     * you must call [View::set_filename].
     *
     *     $view = View::factory($file);
     *
     * @param   string  $file   view filename
     * @param   array   $data   array of values
     * @return  View
     */
    public static function factory($file = NULL, array $data = NULL, $ext = NULL)
    {
        $View = NULL;
        $args = array('file'=> &$file, 'data'=> &$data, 'ext'=> &$ext, 'View'=> &$View);
        Event::trigger('shmvc_view_factory',$args);
        if ($View === NULL)
        {
            $View = new View($file, $data, $ext);
        }
        else if (!($View instanceof SHMVC_View))
        {
            // @todo throw exception for invalid view type
        }

        return $View;
    }

    /**
     * Sets the initial view filename and local data. Views should almost
     * always only be created using [View::factory].
     *
     *     $view = new View($file);
     *
     * @param   string  $file   view filename
     * @param   array   $data   array of values
     * @return  void
     * @uses    View::set_filename
     */
    public function __construct($file = NULL, array $data = NULL, $ext = NULL)
    {
        if ($file !== NULL)
        {
            $this->set_filename($file, $ext);
        }

        if ($data !== NULL)
        {
            // Add the values to the current data
            $this->_data = $data + $this->_data;
        }
    }

    /**
     * Sets the view filename.
     *
     *     $view->set_filename($file);
     *
     * @param   string  $file   view filename
     * @param   string  $ext = NULL   file extension
     * @return  View
     * @throws  View_Exception
     */
    public function set_filename($file,$ext = NULL)
    {
        if (($path = Kohana::find_file('views', $file, $ext)) === FALSE)
        {
            throw new View_Exception('The requested view :file could not be found', array(
                ':file' => $file,
            ));
        }

        // Store the file path locally
        $this->_file = $path;

        return $this;
    }
}