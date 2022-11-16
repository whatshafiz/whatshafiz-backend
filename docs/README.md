# Postman Collection

Bu dizindeki `whatshafiz.postman_collection.json` isimli dosyayı  POSTMAN programına import edin.

Postman içinden yeni bir environment oluşturup aşağıdaki key'leri oluşturun.

```sh
URL=https://api.whatshafiz.com/api/v1
PHONE_NUMBER={{ Üye olduğunuz telefon numarası, örn: 905413582616 }}
PASSWORD={{ Parolanız }}
```

Bu key'leri oluşturduktan sonra Login Requestini kullanarak sisteme login olabilirsiniz.
Token bilginiz otomatik olarak diğer requestlere eklenecek. Yetkiniz olan işlemleri yapabileceksiniz.

Register requestini kullanarak random telefon numarasına sahip yeni bir user oluşturmuş olursunuz. Gereksiz yere bu requesti kullanmayın.

Backend developer arkadaşlar API üzerinde yaptıkları her değişiklik için bu postman collection dosyasını uygun şekilde güncellemelidirler.
