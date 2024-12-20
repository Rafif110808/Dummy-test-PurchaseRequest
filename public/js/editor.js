function encrypter(text) {
  let txt = text;
  for (let n = 0; n < 6; n++) {
    txt = btoa(txt);
  }
  return txt;
}

function decrypter(text) {
  let txt = text;
  for (let n = 0; n < 6; n++) {
    txt = atob(txt);
  }
  return txt;
}
