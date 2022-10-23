# WhatsHafız Backend

Bu repository içinde laravel ve docker kullanarak mobil uygulama ve front arayüz için RESTful API oluşturacağız.

Uygulamamızın CI/CD alyapısı oluşturulmuş durumda:
- Geliştirme sürecinde ana branch olarak `develop` branchini kullanacağız.
- Production branchi olarak `master` branchini kullanacağız.
- `develop` branchine yeni bir PR oluşturduğunuzda otomatik olarak yazılmış testler çalıştırılmakta. 
- `master` branchine merge edilen tüm PR lar otomatik olarak canlıya çıkmakta.

---

Uygulamayı ilk kez kendi bilgisayarınıza kurmak için aşağıdaki komutu çalıştırmanız yeterli.

```sh
sh ./setup.sh
```

Bu komut ile kurulum aşamasında `php-fpm, nginx, mariadb, redis` containerları oluşmakta.  
Kurulum tamamlandıktan sonra `http://localhost:8080` adresinden uygulamaya erişebilirsiniz.

Sonraki kullanımlarda uygulamayı başlatmak için 

```sh
sh ./start.sh
```

Uygulamayı kapatmak için 

```sh
sh ./stop.sh
```

kısayollarını kullanabilirsiniz.

---

Kendi lokalinizdeki port/db/username gibi bilgileri değiştirmek isterseniz ana dizindeki `.env` dosyasının içinden değişiklik yaptıktan sonra tekrar setup çalıştırabilirsiniz.
