<?php namespace App\Console\Commands;

use App\Models\IrcAssociation;
use Illuminate\Console\Command;
use Tymon\JWTAuth\Facades\JWTAuth;

class UpdateIrc extends Command {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'irc:listen';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start listening for IRC associations';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //
        //fork() && exit;
        while (1)
        {
            $file = config('app.irc_pipe');

            if (!file_exists($file))
            {
                posix_mkfifo($file, 0644);
            }
            $fh = fopen($file, 'r');

            while ( ($line = fgets($fh)) !== false )
            {
                try
                {
                    $line = rtrim($line);
                    var_dump($line);
                    $parts = explode(" ", $line);

                    $user = JWTAuth::toUser($parts[1]);

                    $assoc = IrcAssociation::findOrCreate($user->id, $parts[0]);
                    var_dump($line);
                }
                catch (Exception $e)
                {
                    error_log($e->getMessage());
                }
                catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e)
                {
                    error_log($e->getMessage());
                }
                catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e)
                {
                    error_log($e->getMessage());
                }
                catch(\Tymon\JWTAuth\Exceptions\InvalidClaimException $e)
                {
                    error_log($e->getMessage());
                }
                catch(\Tymon\JWTAuth\Exceptions\PayloadException $e)
                {
                    error_log($e->getMessage());
                }
                catch(\Tymon\JWTAuth\Exceptions\TokenBlacklistedException $e)
                {
                    error_log($e->getMessage());
                }
                catch (JWTException $e)
                {
                    error_log($e->getMessage());
                }
            }
        }
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
        ];
    }
}
