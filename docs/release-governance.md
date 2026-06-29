# DMS Release Governance

Dokumen ini adalah sumber kerja untuk perubahan DMS. Tujuannya supaya setiap perubahan punya status Kanban yang jelas, test yang bisa dibuktikan, smoke production, commit, dan push sebelum dianggap selesai.

## Kanban Status

- `Backlog`: scope sudah tercatat, belum siap dieksekusi.
- `Ready`: scope sudah cukup jelas dan siap diambil.
- `In Progress`: implementasi atau audit aktif sedang berjalan.
- `Testing`: implementasi selesai dan sedang masuk targeted test, full suite, cache restore, dan smoke production.
- `Review`: pekerjaan utama sudah selesai, tapi masih ada scope tersisa, child card, atau keputusan bisnis yang belum final.
- `Done`: checklist selesai 100%, test dan smoke hijau, commit sudah push, dan comment closure sudah ditulis.

Card yang checklist-nya belum 100% tidak boleh dipindah ke `Done`, kecuali scope tersisa sudah dipindahkan ke child card dan closure parent mencatat keputusan tersebut.

## Checklist Wajib Per Card

Setiap card A-Z minimal punya checklist berikut:

- Scope pekerjaan dan acceptance criteria.
- Analisis impact ke modul terkait.
- Analisis risiko service lain, khususnya Central, BMP, dan domain DMS.
- Implementasi backend, database, controller, service, UI, atau docs sesuai scope.
- Targeted test untuk area yang berubah.
- Full suite sebelum closure.
- Production cache restore.
- Smoke critical routes.
- Commit dan push.
- Comment closure di Kanban.

## Deployment Guard

Sebelum card masuk `Done`, jalankan urutan ini di `/var/www/kurmigo-dms`:

```bash
php8.3 artisan optimize:clear
php8.3 artisan test
php8.3 artisan config:cache
php8.3 artisan event:cache
php8.3 artisan view:cache
bash deploy/smoke-production.sh
```

Jika perubahan hanya docs/Kanban governance, targeted test boleh kecil, tapi full suite dan smoke tetap wajib sebelum commit/push.

## Critical Smoke Routes

`deploy/smoke-production.sh` wajib menjaga route berikut:

- Central HTTP: `http://31.97.106.123/central`
- Central HTTPS: `https://31.97.106.123/central`
- BMP Auth: `https://31.97.106.123/dev/bmp/bmp_report/Auth`
- DMS Login: `https://dms.kurmigo.id/login`
- DMS Health: `https://dms.kurmigo.id/health`

Jika ada service production lain yang pernah terdampak, route-nya harus ditambahkan ke smoke script sebelum lanjut modul baru.

## Kanban Comment Standard

Gunakan comment singkat tapi lengkap:

- Start: sebut card, tujuan, dan fokus kerja.
- Progress: sebut apa yang sudah selesai dan test yang sudah hijau.
- Testing: sebut targeted/full suite yang akan atau sudah dijalankan.
- Closure: sebut commit hash, hasil full suite, hasil smoke, dan alasan card boleh `Done`.

## Commit Standard

Commit harus kecil dan menjelaskan outcome, misalnya:

- `Add-SaaS-health-callback-dispatch`
- `Preserve-order-price-snapshots-on-edit`
- `Polish-price-impact-approval-state`

Jangan menggabungkan perubahan unrelated dalam satu commit. Jika scope membesar, pecah menjadi child card.
