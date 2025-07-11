<div align="left">
  <img src="https://github.com/mainbotan/Rift-Doc/blob/dev/public/f45e72cf-1653-429a-bf7b-d044b921fbee.jpeg" alt="Logo" width="44%" />
</div>

# Rift Miniframework

Rift is far from Laravel for quick and easy MVP creation. If you are used to controllers (which in most cases grow into divine objects), ORM and server rendering - run away.
If you don't want to get out of the habit using Blade, close this documentation. If you are not ready for the pain, the unlearning of exceptions, and a clear contract between the layers, close the tab. If your goal is to create landing page for the company, run away even faster. If you work in a team that is used to a ton of legacy shit (we will often use this scientific term in this documentation), run.

But if you have decided to stay, after you understand the basic concept, the Rift should give you real pleasure. Due to the specifics of architecture and contractuality, your application can turn into an easily readable and predictable structure ready for smooth scaling. 

First of all, Rift was conceived as a means to write API gateways that provide only data without templates. Keep this fact in mind. In addition, due to the abandonment of controllers in favor of 
minimalistic and atomic UseCases, you can easily reuse the code, thereby breaking all the logic into small independent parts. 

The Rift is also focused on multi-tenancy. That is why we have long considered Postgres to be the preferred database (however, for fans of the classical approach, you can easily use MySQL). 
We deliberately abandoned the ORM layer in favor of maximum developer control. Rift Configurators will help with the deployment of your projects and make initializing new tenants even easier.

If you are still here, we have news for you. Thanks to a single contract for all layers, Rift applications can be used not only on top of HTTP. Queues, CLIs, and other vagaries will become easier.
Thus, Rift is a rough, uncouth and obviously losing out in many aspects of its framework. But it is he who gives you maximum control over the architecture, strict contractuality and reusability.

God bless us!
