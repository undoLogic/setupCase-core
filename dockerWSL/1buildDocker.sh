docker volume prune -f
docker-compose down

sleep 1

docker-compose build --no-cache

sleep 1
docker-compose up -d

sleep 10
