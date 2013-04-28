//************
//opis systemu
//************
int init() {return(0);}
int deinit() {return(0);}
int prevbar = 1; //domyslny numer swieczki poprzedniej
extern int padding = 60; //odleglosc miedzy min/maks a miejscem w ktorym otworzy sie zlecenie
int start() { //funkcja glowna
   if (Bars != prevbar) { //jesli obecna swieczka ma inny numer od poprzedniej
      Print("nowa swieczka!");
      prevbar = Bars;
      if ((Ask+padding*Point) < High[1]) {
         Print("Moze zlozyc zlecenie buystop na poziomie maksimum poprzedniej swieczki");
      }
      else {
         Print("Nie zlozyl zlecenia buystop bo maksimum poprzedniej swieczki jest zbyt blisko");
      }
      if ((Bid-padding*Point) > Low[1]) {
         Print("Moze zlozyc zlecenie sellstop na poziomie minimum poprzedniej swieczki");
      }
      else {
         Print("Nie zlozyl zlecenia sellstop bo minimum poprzedniej swieczki jest zbyt blisko");
      }
   }
   Comment(
      "Aktualna swieczka: "+Bars+"\n"
      ,"Poprzednia swieczka: "+prevbar+"\n"
   );
}
