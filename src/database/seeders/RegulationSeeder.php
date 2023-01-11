<?php

namespace Database\Seeders;

use App\Models\Regulation;
use Illuminate\Database\Seeder;

class RegulationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $hafizol = [
            'name' => 'HafızOl',
            'slug' => 'hafizol',
            'text' => "1. Whatshafız'ın Tanımı ve Amacı:
            Ezberleme açısından daha sağlam ve daha az vakit alan yeni hafızlık sistemidir. Öğrencinin hafızlığı tamamlanmasının ardından ömrün sonuna kadar ezberini koruması amaçlanmaktadır.
            2. Whatshafız'a Üyelik:
            Whatshafıza öğrenci olarak girebilmek için Kur'an-ı Kerim mahreçlerini düzgün okuyabilme seviyesi ile seri okuma seviyesi istenir. Mezuniyeti ve yaşı önemli değildir.
            3. Öğrencilerin Gruba Kabul Edilişi:
            3.1 Hafızkal öğrenciler, hafızol öğrencilerinin mülakatını yapıp öğrencinin okuyuşunu ya da mahrecini uygun bulurlarsa, onu kabul ederler. Uygun değil ise, gruptan çıkarırlar. Hafızkal öğrencileri, öğrencinin hafızlık için uygun olup olmadığını kontrol ederken Kur’an’ı yüzünden okuma ile mahrecini dikkate alacaktır.
            3.2 Eğer hafızkal öğrencisi, hafızol öğrencisinin mahrecini uygun bulmaz ise, öğrenci başka bir hocaya mahrecini dinletemeyecektir. Gruptan ayrılacaktır. Herhangi bir Kur’an kursundan mahreçlerini düzeltip bir sonraki dönemde tekrar bize kayıt yaptırabilecektir.
            4. Yeni Hafızlık Yapanların Program İşleyişi (Hafızol Programı)
            4.1 Hafızkal öğrencileri, hafızol öğrencisini dinlerken öğrenciye sayfa başı en fazla 2 dakika süre tanıyacaktır. Öğrenci hocayı bir sayfa ezber vermek için, 2 dakikadan daha fazla oyalayamaz. Bu durumda öğrenci küme düşer.",
           
            'summary' => "1. Whatshafız'ın Tanımı ve Amacı:
            Ezberleme açısından daha sağlam ve daha az vakit alan yeni hafızlık sistemidir. Öğrencinin hafızlığı tamamlanmasının ardından ömrün sonuna kadar ezberini koruması amaçlanmaktadır.
            2. Whatshafız'a Üyelik:
            Whatshafıza öğrenci olarak girebilmek için Kur'an-ı Kerim mahreçlerini düzgün okuyabilme seviyesi ile seri okuma seviyesi istenir. Mezuniyeti ve yaşı önemli değildir.
            3. Öğrencilerin Gruba Kabul Edilişi:
            3.1 Hafızkal öğrenciler, hafızol öğrencilerinin mülakatını yapıp öğrencinin okuyuşunu ya da mahrecini uygun bulurlarsa, onu kabul ederler. Uygun değil ise, gruptan çıkarırlar. Hafızkal öğrencileri, öğrencinin hafızlık için uygun olup olmadığını kontrol ederken Kur’an’ı yüzünden okuma ile mahrecini dikkate alacaktır.
            3.2 Eğer hafızkal öğrencisi, hafızol öğrencisinin mahrecini uygun bulmaz ise, öğrenci başka bir hocaya mahrecini dinletemeyecektir. Gruptan ayrılacaktır. Herhangi bir Kur’an kursundan mahreçlerini düzeltip bir sonraki dönemde tekrar bize kayıt yaptırabilecektir.
            4. Yeni Hafızlık Yapanların Program İşleyişi (Hafızol Programı)
            4.1 Hafızkal öğrencileri, hafızol öğrencisini dinlerken öğrenciye sayfa başı en fazla 2 dakika süre tanıyacaktır. Öğrenci hocayı bir sayfa ezber vermek için, 2 dakikadan daha fazla oyalayamaz. Bu durumda öğrenci küme düşer.",
        ];
        $hafizkal = [
            'name' => 'HafızKal',
            'slug' => 'hafizkal',
            'text' => "1. Whatshafız Hafızkal'ın Tanımı:
                Ezberleme açısından daha sağlam ve daha az vakit alan yeni hafızlık sistemidir.
                2. Hafızkal'a Üyelik:
                Hafızkal programına girebilmek için daha önce hafızlığınızı tamamlamış olmanız gerekmektedir. Mezuniyeti ve yaşı önemli değildir. Resmî bir belgenizin bulunması gerekmez.
                3. Amaç:
                Daha önce hafızlık yapmış öğrencilerin hafızlıklarını ömür boyu korumalarını sağlamak. Bunun için öncelikle ezberleri tekrar edilerek sayfalar sağlama alınacaktır. 8 aylık tekrar programından sonra isteyen öğrenciler ömür boyu programda devam edebileceklerdir. Yılın belirli aylarında ezberler tekrar ettirilecektir.
                4. İşleyiş
                4.1 Verilen sayfa ezber tablosunda eşleşen öğrenciler o günün sayfalarını birbirlerine verirler. Her sayfa için 2 dakikadan daha fazla süre verilemez. Bir sayfayı ezber verme süresi 2 dakikayı aşarsa öğrenci küme düşer.",
            'summary' => "1. Whatshafız'ın Tanımı ve Amacı:
                Ezberleme açısından daha sağlam ve daha az vakit alan yeni hafızlık sistemidir. Öğrencinin hafızlığı tamamlanmasının ardından ömrün sonuna kadar ezberini koruması amaçlanmaktadır.
                2. Whatshafız'a Üyelik:
                Whatshafıza öğrenci olarak girebilmek için Kur'an-ı Kerim mahreçlerini düzgün okuyabilme seviyesi ile seri okuma seviyesi istenir. Mezuniyeti ve yaşı önemli değildir.
                3. Öğrencilerin Gruba Kabul Edilişi:
                3.1 Hafızkal öğrenciler, hafızol öğrencilerinin mülakatını yapıp öğrencinin okuyuşunu ya da mahrecini uygun bulurlarsa, onu kabul ederler. Uygun değil ise, gruptan çıkarırlar. Hafızkal öğrencileri, öğrencinin hafızlık için uygun olup olmadığını kontrol ederken Kur’an’ı yüzünden okuma ile mahrecini dikkate alacaktır.
                3.2 Eğer hafızkal öğrencisi, hafızol öğrencisinin mahrecini uygun bulmaz ise, öğrenci başka bir hocaya mahrecini dinletemeyecektir. Gruptan ayrılacaktır. Herhangi bir Kur’an kursundan mahreçlerini düzeltip bir sonraki dönemde tekrar bize kayıt yaptırabilecektir.
                4. Yeni Hafızlık Yapanların Program İşleyişi (Hafızol Programı)
                4.1 Hafızkal öğrencileri, hafızol öğrencisini dinlerken öğrenciye sayfa başı en fazla 2 dakika süre tanıyacaktır. Öğrenci hocayı bir sayfa ezber vermek için, 2 dakikadan daha fazla oyalayamaz. Bu durumda öğrenci küme düşer.",
            ];
        $whatsenglish = [
            'name' => 'WhatsEnglish',
            'slug' => 'whatsenglish',
            'text' => "1. WhatsEnglish'in Tanımı:
                1a) WhatsEnglish, ücretsiz olarak Öğr. Gör. Dr. Muhammet Ali Can ve gönüllü ekibi tarafından Arapça ve Hafızlık gibi uzaktan eğitim sistemi ile verilen hizmetlerden bir başkasıdır.
                1b) Toplam 2 kurdan oluşmaktadır. Her kur 25 haftalıktır. Türkiye Türkçesi dilbilgisi kurallarına uygun ve ingilizceyi hiç bilmeyenlere yönelik şekilde hazırlanan programda, İngilizce gramer konularının büyük çoğunluğu tamamlanmaktadır. Toplamda yaklaşık 1000 kelime ezberlenmektedir.",
            'summary' => "1. WhatsEnglish'in Tanımı:
                1a) WhatsEnglish, ücretsiz olarak Öğr. Gör. Dr. Muhammet Ali Can ve gönüllü ekibi tarafından Arapça ve Hafızlık gibi uzaktan eğitim sistemi ile verilen hizmetlerden bir başkasıdır.
                1b) Toplam 2 kurdan oluşmaktadır. Her kur 25 haftalıktır. Türkiye Türkçesi dilbilgisi kurallarına uygun ve ingilizceyi hiç bilmeyenlere yönelik şekilde hazırlanan programda, İngilizce gramer konularının büyük çoğunluğu tamamlanmaktadır. Toplamda yaklaşık 1000 kelime ezberlenmektedir.",
        ];
        $whatsarapp = [
            'name' => 'WhatsArapp',
            'slug' => 'whatsarapp',
            'text' => "1. WhatsArapp'ın Tanımı:
            1a) WhatsArapp, ücretsiz olarak Öğr. Gör. Dr. Muhammet Ali Can ve gönüllü ekibi tarafından İngilizce ve Hafızlık gibi uzaktan eğitim sistemi ile verilen hizmetlerden bir başkasıdır.
            1b) Toplam 4 kurdan oluşmaktadır. İlk iki kur okuma, konuşma, anlama ve cümle kurmaya yöneliktir Standart Fasih Arapça temelli olup, konuşma diline de yer verilmektedir. Üçüncü kurda Kuran-ı Kerim Arapçası işlenir. Dördüncü kur ise YDS’ye yöneliktir.",
            'summary' => "1. WhatsArapp'ın Tanımı:
            1a) WhatsArapp, ücretsiz olarak Öğr. Gör. Dr. Muhammet Ali Can ve gönüllü ekibi tarafından İngilizce ve Hafızlık gibi uzaktan eğitim sistemi ile verilen hizmetlerden bir başkasıdır.
            1b) Toplam 4 kurdan oluşmaktadır. İlk iki kur okuma, konuşma, anlama ve cümle kurmaya yöneliktir Standart Fasih Arapça temelli olup, konuşma diline de yer verilmektedir. Üçüncü kurda Kuran-ı Kerim Arapçası işlenir. Dördüncü kur ise YDS’ye yöneliktir.",
            
        ];

        Regulation::create($hafizol);
        Regulation::create($hafizkal);
        Regulation::create($whatsenglish);
        Regulation::create($whatsarapp);
    }
}
