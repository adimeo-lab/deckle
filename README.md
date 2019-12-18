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
 - [Virtual Box](http://www.virtualbox.org)
 - [Homebrew](http://www.homebrew.com)
 - [Mutagen](http://www.mutagen.io)
 - [Vagrant](http://www.vagrantup.com)     


## Paramétrage

### Installation des dépendances

La majorité des outils en ligne de commande nécessaires au bon fonctionnement de Deckle sont soit natifs 
sur macOs soit installables avec `brew`. L'installateur de Deckle procédera à l'installation de ces dépendances.  

#### Virtual Box

Téléchargez l'installateur sur le [site officiel](https://www.virtualbox.org/wiki/Downloads).


#### Mutagen

Pour assurer des performances maximales, nous n'utilisons pas de montage de volume entre l'hôte et le container, mais un 
outil de synchronisation ultra performant qui installe un daemon dans le container et assure en permanence une synchronisation
bi-directionelle entre l'hôte et le container, avec une precedence donnée à l'hôte.

Cet outil s'appelle `mutagen` et s'installe sous macOs à l'aide de `brew`.   

NOTE : Si vous tentez d'utilisez Deckle sur une autre plateforme que macOs (Windows et Linux notamment), il faut télécharger le package sur [la page GitHub](https://github.com/mutagen-io/mutagen/releases) du projet.

### Fine tuning du Mac

#### Authentification SSH

Le plus confortable pour se connecter  en SSH sera le recours à une clé SSH, et une configuration de votre client.

Le préalable est donc de disposer d'une paire de clés RSA associée à son compte utilisateur macOs. Idéalement, cette même 
clé devrait être associée à votre compte GitHub mais ce n'est pas indispensable.

La première étape consiste donc à copier votre clé publique sur la VM :

```shell script
ssh-copy-id deckle@deckle-vm
```

Ensuite, il faut indiquer à SSH que l'on souhaite propager sa clé privée dans les sessions distantes (ce qui permet de ne PAS 
la copier physiquement dans la VM) en éditant votre fichier de config SSH :


```shell script
vi ~/.ssh/config
```

Et en y ajoutant la section suivante :

```apacheconfig
Host deckle-vm
        Hostname deckle-vm
        User deckle
        UseKeychain yes
        AddKeysToAgent yes
        ServerAliveInterval 60
        ForwardAgent Yes

```




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
deckle sync monitor
```

### Installation des dépendances


Pour installer les dépendances de `composer`, exécutez :

```shell script
deckle composer install
```

NOTE: si vous voulez passer des paramètres à `composer`, il faut les faire précéder de la séquence `--` afin
que Deckle les ignore.  

### Ouvrir un shell dans le container principal

Pour accéder rapidement aux images de l'environnement en shell, exécutez :

```shell script
deckle sh
```


# Autres services

## Portainer

http://portainer.deckle.local
admin/portainer

## Traefik
http://traefik.deckle.local

## phpMyAdmin

http://pma57.deckle.local

# Etendre Deckle

## Commandes locales

Si vous souhaitez écrire une commande spécifique à un projet, vous pouvez créer une classe dans `./deckle/commands`.

Le fichier contenant la classe doit s'appeler `*Command.php` et étendre `AdimeoLab\Deckle\AbstractDeckleCommand`.


| Attention : pour développer votre commande, il vous faudra installer les sources de Deckle dans votre dossier `./deckle`,
| mais ces sources ne seront pas celles utilisées à l'exécution. A la place, ce sont celles embarquées dans le binaire 
| `deckle(.phar)` qui le seront. Il n'y a pour l'heure aucun mécanisme de contrôle de versions pour les commandes locales.


# Problèmes fréquents

## Certaines commandes impliquant une connexion SSH depuis la VM échouent avec le message 'Host key verification failed.
'

Le message `Host key verification failed.` signifie en général que la machine distante n'est pas connue de la VM (i.e. 
que son identité est absente du fichier `~/.ssh/known_hosts`). 

Pour remédier à ce problème, il faut faire une première connexion depuis la VM elle-même sur la machine concernée.

## La résolution des hôtes *.deckle.local ne fonctionne pas

Si ce problème survient, tentez de redémarrer le sous-système DNS de macOs comme suit : 

```shell script
dscacheutil -flushcache
sudo killall -HUP mDNSResponder
``` 

En cas de problème avec la mise en oeuvre de DNSmasq, consulter [ce blog](https://www.stevenrombauts.be/2018/01/use-dnsmasq-instead-of-etc-hosts/#configure-as-default-dns-resolver-in-macos)
