//************
//opis systemu
//************
int init() {return(0);}
int deinit() {return(0);}
int prevbar = 1; //domyslny numer swieczki poprzedniej
extern int padding = 60; //odleglosc miedzy min/maks a miejscem w ktorym otworzy sie zlecenie

void prepareTransakction(string type, string msgSuccess, string msgError) {
   bool whileDelimiter = true;
   int whileIndex = 1;
   while(whileDelimiter) {
   bool condidion;
   if (type == "buystop") {
      if ((Ask+padding*Point) < High[whileIndex]) condidion = true;
      else condidion = false;
   }
   else if (type == "sellstop") {
      if ((Bid-padding*Point) > Low[whileIndex]) condidion = true;
      else condidion = false;
   }
   Print(type);
   if (condidion) {
      Print(msgSuccess);
      whileDelimiter = false;
   }
   else {
      Print(msgError);
      if (whileIndex >= 10) {
         whileDelimiter = false;
         Print("...");
      }
   }
   whileIndex++;
   }
}

int start() { //funkcja glowna
   if (Bars != prevbar) { //jesli obecna swieczka ma inny numer od poprzedniej
      Print("nowa swieczka!");
      prevbar = Bars;
      prepareTransakction(
         "buystop",
         "Moze zlozyc zlecenie buystop na poziomie maksimum poprzedniej swieczki",
         "Nie zlozyl zlecenia buystop bo maksimum poprzedniej swieczki jest zbyt blisko"
      );
      prepareTransakction(
         "sellstop",
         "Moze zlozyc zlecenie sellstop na poziomie minimum poprzedniej swieczki",
         "Nie zlozyl zlecenia sellstop bo minimum poprzedniej swieczki jest zbyt blisko"
      );
   }
   Comment(
      "Aktualna swieczka: "+Bars+"\n"
      ,"Poprzednia swieczka: "+prevbar+"\n"
   );
}
