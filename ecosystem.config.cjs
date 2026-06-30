module.exports = {
  apps: [
    {
      name: 'absensicipta-worker',
      script: 'artisan',
      args: 'queue:work database --sleep=3 --tries=3 --max-time=3600',
      interpreter: 'php', // Kunci utamanya: Beri tahu PM2 untuk memakai PHP
      instances: 2,       // Menjalankan 2 worker sekaligus
      exec_mode: 'fork',
      watch: false,
      autorestart: true,
      error_file: './storage/logs/worker-error.log',
      out_file: './storage/logs/worker-out.log'
    },
    {
      name: 'absensicipta-scheduler',
      script: 'artisan',
      args: 'schedule:work', // Fitur daemon cron milik Laravel
      interpreter: 'php',
      instances: 1,
      exec_mode: 'fork',
      watch: false,
      autorestart: true,
      error_file: './storage/logs/scheduler-error.log',
      out_file: './storage/logs/scheduler-out.log'
    }
  ]
};
