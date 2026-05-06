@echo off
cd /d "c:\Users\youssef\Desktop\projects\rifimediatv\backend"
echo Resetting stuck playlists...
php artisan tinker --execute="App\Models\Playlist::whereIn('status', ['queued', 'processing'])->update(['status' => 'pending']); echo 'Playlists reset. Now run the server.';"
echo Clearing config cache...
php artisan config:clear
php artisan cache:clear
echo Done. Now visit the dashboard and click Re-parse on each playlist.
pause
