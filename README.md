# Deckle : automatisation du montage d'environnements de développement virtualisés

## Concept

Parce que Docker ne fonctionne (correctement) que sous Linux, et que nous sommes nombreux à travailler sous Mac,
nous avons choisi de faire tourner nos environnements de référence dans une machine virtuelle Ubuntu.

L'administration d'un tel environnement peut s'avérer complexe, voire déroutant pour un junior. Deckle a vocation
à simplifier cette administration en automatisant de nombreuses tâches, aussi bien en rapport avec le 
 déploiement de l'environnement qu'avec son utilisation quotidienne.  


## Composants

Pour fonctionner de manière optimale, l'ecosystème Deckle requiert les compsoants suivants :

 - macOs :)
 - [Parallels Desktop](http://www.parallels.com)
 - [Homebrew](http://www.homebrew.com)
 - [Mutagen](http://www.mutagen.io)
 - Une VM préconfigurée (lien à venir)     

NOTE : Deckle fonctionnera de la même manière avec tout autre moteur de virtualisation, mais sur macOs, il se révèle 
particulièrement performant et ergonomique.

## Paramétrage

### Installation des dépendances

La majorité des outils en ligne de commande nécessaires au bon fonctionnement de Deckle sont soit natifs 
sur macOs soit installables avec `brew`.

#### Parallels Desktop

Télécharger l'installateur sur [le site de l'éditeur](http://www.parallels.com), puis suivre les instructions 
d'isntallation.

Télécharger ensuite la dernière image disponible sur [le site de Deckle](https://deckle.io/images/paralles/desckle-latest.pvm)

Une fois la machine démarée, penser à récupérer sont adresse IP, que l'on référencera plsu tard dans ce document `IPVM`.

Il est également vivement recommander d'ajouter la ligne suivante au fichier `/etc/hosts` du Mac :

```shell script
sudo bash -c "echo 'IPVM    ubuntu-dev-server' >> /etc/hosts'"
```

#### Git

Evidemment, il vous faudra `git` :

```shell script
brew install git
```
#### Docker

Nous utiliserons également le client `docker` (le serveur se trouve dans la VM) :

```shell script
brew install docker
```


#### Dnsmasq

Pour permettre la résolution des domaines *.docker.local, il faudra utiliser dnsmasq, un pseudo-serveur DNS local.

Celui-ci s'installe également avec `brew` :

```shell script
brew install dnsmasq
```

Sa configuration est assez simple :

D'abord, créer la configuration qui permettra la résolution des domaines `*.docker.local` dans `dnsmasq` :

```shell script
echo 'address=/.docker.local/IPVM' >> /usr/local/etc/dnsmasq.conf
```  

NOTE: bien penser à remplacer `IPVM` par la véritable adresse IP de votre machine.

Enfin, dire à macOs d'utiliser dnsmasq pour résoudre toutes les requêtes DNS portant sur les domaines se terminant 
par `docker.local` :

```shell script
sudo mkdir /etc/resolver
sudo bash -c 'echo "nameserver 127.0.0.1" > /etc/resolver/docker.local'
dscacheutil -flushcache
sudo killall -HUP mDNSResponder
``` 

En cas de problème avec la mise en oeuvre de DNSmasq, consulter [ce blog](https://www.stevenrombauts.be/2018/01/use-dnsmasq-instead-of-etc-hosts/#configure-as-default-dns-resolver-in-macos)

#### Mutagen

Pour assurer des performances maximales, nous n'utilisons pas de montage de volume entre l'hôte et le container, mais un 
outil de synchronisation ultra performant qui installe un daemon dans le container et assure en permanence une synchronisation
bi-directionelle entre l'hôte et le container, avec une precedence donnée à l'hôte.

Cet outil s'appelle `mutagen` et s'installe sous macOs à l'aide de `brew` :

```shell script
brew install mutagen-io/mutagen/mutagen
```  

NOTE : Si vous tentez d'utilisez Deckle sur une autre plateforme que macOs (Windows et Linux notamment), il faut télécharger le package sur [la page GitHub](https://github.com/mutagen-io/mutagen/releases) du projet.

### Fine tuning du Mac

#### Authentification SSH

#### Déclation de l'hôte Docker


## Travailler avec Deckle

### Initialisation de l'environnement

Pour monter l'environnement de développement, il suffit d'appeler la commande suivante :

```shell script
cd project/diretory
deckle init
```

La commande `init` est interactive : elle va vous demander quelles opérations elle doit ou non raliser. La 
plupart de ces opérations sont détaillées ci-dessous.

### Synchronisation des fichiers sources dans l'image

Chaque projet doit fournir un fichier `deckle/mutagen.yml` pour fonctionner correctement avec Deckle. Pour démarrer la
synchronisation, exécuter :

````shell script
cd project/directory
deckle sync start
````

Pour contrôler le bon fonctionnement de la synchronisation :

```shell script
deckle sync status
```

### Définition des droits


Selon les projets, il est souvent nécessaire de fiwer des permissions spécifiques sur les sources. Deckle
fournit une commande pour cela. Cette commande est dépendante du type de projet (drupal8, sf4, ...) :  

```shell script
deckle permissions:fix
```

### Installation des dépendances


Pour installer les dépendances de `composer`, exécutez :

```shell script
deckle composer install
```

NOTE: si vous voulez passer des paramètres à `composer`, il faut les faire précéder de la séquence `--` afin
que Deckle les ignore.  

### Ouvrir un shell

Pour accéder rapidement aux images de l'environnement en shell, exécutez :

```shell script
deckle sh
```
