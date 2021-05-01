<?php
namespace app\api\controllers\front
{

    use app\main\models\ModelUser;
    use core\application\RestController;

    class index extends RestController
    {

        public function index()
        {

        }

        public function registerUserEmail () {
            if (!isset($_POST['email']) || empty($_POST['email'])) {
                $this->throwError(
                    422, "Parameter [email] not found"
                );
            }
            $email = $_POST['email'];
            // Check regex email
            if (!preg_match('/(?:[a-z0-9!#$%&\'*+\/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&\'*+\/=?^_`{|}~-]+)*|"(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21\x23-\x5b\x5d-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])*")@(?:(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?|\[(?:(?:(2(5[0-5]|[0-4][0-9])|1[0-9][0-9]|[1-9]?[0-9]))\.){3}(?:(2(5[0-5]|[0-4][0-9])|1[0-9][0-9]|[1-9]?[0-9])|[a-z0-9-]*[a-z0-9]:(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21-\x5a\x53-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])+)\])/', $email, $output_array)) {
                $this->throwError(
                    422, "Parameter [email] incorrectly formatted"
                );
            }

            // insert email
            $mUser = new ModelUser();
            if (!$mUser->insert(array("email_user" => $email))) {
                $this->throwError(
                    422, "Error during registration"
                );
            }
        }
    }
}