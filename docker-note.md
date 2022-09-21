sudo docker-compose up -d
sudo docker-compose ps
sudo docker-compose exec SERVICE COMMAND...
sudo docker-compose exec api ls -la
sudo docker-compose exec api composer install
sudo docker-compose exec api php artisan migrate:fresh --seed