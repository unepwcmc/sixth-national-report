desc 'Restarts the application calling the appropriate Unicorn shell script.'
task :restart_nginx do
  on roles(:app) do
    execute 'sudo service nginx reload'
  end
end
